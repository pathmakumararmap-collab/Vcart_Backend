<?php

namespace App\Services;

use App\Exceptions\InvalidCouponException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\CouponRepositoryInterface;
use Illuminate\Support\Collection;

class CouponService
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    /**
     * @param  Collection<int, array{product_id: int, category_id: int}>  $lineItems
     */
    public function validateForOrder(string $code, float $subtotal, ?User $user, Collection $lineItems): Coupon
    {
        $coupon = $this->coupons->findByCode($code);

        if (! $coupon) {
            throw new InvalidCouponException("Coupon \"{$code}\" does not exist.");
        }

        if (! $coupon->isValid()) {
            throw new InvalidCouponException("Coupon \"{$code}\" is not currently valid.");
        }

        if ($subtotal < $coupon->min_order_amount) {
            throw new InvalidCouponException("Order subtotal must be at least {$coupon->min_order_amount} to use this coupon.");
        }

        if ($user && $coupon->usage_limit_per_user) {
            $usedByUser = $coupon->usages()->where('user_id', $user->id)->count();

            if ($usedByUser >= $coupon->usage_limit_per_user) {
                throw new InvalidCouponException('You have already used this coupon the maximum number of times.');
            }
        }

        if ($coupon->applicable_to === 'product') {
            $allowedProductIds = $coupon->products()->pluck('products.id');
            $matches = $lineItems->pluck('product_id')->intersect($allowedProductIds)->isNotEmpty();

            if (! $matches) {
                throw new InvalidCouponException('This coupon does not apply to any items in your cart.');
            }
        }

        if ($coupon->applicable_to === 'category') {
            $allowedCategoryIds = $coupon->categories()->pluck('categories.id');
            $matches = $lineItems->pluck('category_id')->intersect($allowedCategoryIds)->isNotEmpty();

            if (! $matches) {
                throw new InvalidCouponException('This coupon does not apply to any items in your cart.');
            }
        }

        return $coupon;
    }

    public function calculateDiscount(Coupon $coupon, float $subtotal, ?Collection $lineItems = null): float
    {
        $eligibleSubtotal = $this->eligibleSubtotal($coupon, $subtotal, $lineItems);

        $discount = $coupon->type === 'percentage'
            ? $eligibleSubtotal * ($coupon->value / 100)
            : $coupon->value;

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        return round(min($discount, $eligibleSubtotal), 2);
    }

    /**
     * A product/category-scoped coupon (e.g. a Flash Sale) must only
     * discount the items it actually applies to — not the whole cart just
     * because one eligible item happens to be in it alongside others.
     *
     * @param  Collection<int, array{product_id: int, category_id: int, quantity: int, unit_price: float}>|null  $lineItems
     */
    private function eligibleSubtotal(Coupon $coupon, float $fullSubtotal, ?Collection $lineItems): float
    {
        if ($lineItems === null || $coupon->applicable_to === 'all') {
            return $fullSubtotal;
        }

        if ($coupon->applicable_to === 'product') {
            $allowedProductIds = $coupon->products()->pluck('products.id');

            return (float) $lineItems
                ->filter(fn (array $line) => $allowedProductIds->contains($line['product_id']))
                ->sum(fn (array $line) => $line['quantity'] * $line['unit_price']);
        }

        if ($coupon->applicable_to === 'category') {
            $allowedCategoryIds = $coupon->categories()->pluck('categories.id');

            return (float) $lineItems
                ->filter(fn (array $line) => $allowedCategoryIds->contains($line['category_id']))
                ->sum(fn (array $line) => $line['quantity'] * $line['unit_price']);
        }

        return $fullSubtotal;
    }

    public function recordUsage(Coupon $coupon, Order $order, ?User $user, float $discountAmount): void
    {
        $coupon->usages()->create([
            'order_id' => $order->id,
            'user_id' => $user?->id,
            'discount_amount' => $discountAmount,
        ]);

        $coupon->increment('used_count');
    }
}