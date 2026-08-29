<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single entry point for creating an order from ANY sales channel
 * (website checkout, imported Facebook order, or outlet POS sale).
 * Every code path ends up calling InventoryService::deduct(), which is
 * what guarantees all three channels share one inventory.
 */
class OrderService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CouponService $coupons,
        private readonly WarehouseRepositoryInterface $warehouses,
    ) {}

    public function placeOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $warehouse = isset($data['warehouse_id'])
                ? Warehouse::query()->findOrFail($data['warehouse_id'])
                : $this->warehouses->default();

            abort_if($warehouse === null, 422, 'No warehouse available to fulfill this order.');

            $user = isset($data['user_id']) ? User::query()->find($data['user_id']) : null;

            $lineItems = $this->resolveLineItems($data['items']);

            $subtotal = $lineItems->sum(fn (array $line) => $line['quantity'] * $line['unit_price']);
            $taxAmount = $lineItems->sum(fn (array $line) => round($line['quantity'] * $line['unit_price'] * ($line['tax_rate'] / 100), 2));

            $discountAmount = 0.0;
            $coupon = null;

            if (! empty($data['coupon_code'])) {
                $coupon = $this->coupons->validateForOrder(
                    $data['coupon_code'],
                    $subtotal,
                    $user,
                    $lineItems->map(fn (array $line) => ['product_id' => $line['product']->id, 'category_id' => $line['product']->category_id]),
                );

                $discountAmount = $this->coupons->calculateDiscount($coupon, $subtotal);
            }

            $shippingAmount = (float) ($data['shipping_amount'] ?? 0);
            $totalAmount = round($subtotal - $discountAmount + $taxAmount + $shippingAmount, 2);

            $source = $data['source'];

            $order = Order::query()->create([
                'order_no' => $this->generateOrderNumber($source),
                'source' => $source,
                'channel_reference' => $data['channel_reference'] ?? null,
                'user_id' => $user?->id,
                'warehouse_id' => $warehouse->id,
                'status' => $source === 'pos' ? 'completed' : 'pending',
                'payment_status' => 'unpaid',
                'coupon_id' => $coupon?->id,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'currency' => $data['currency'] ?? 'LKR',
                'shipping_address_id' => $data['shipping_address_id'] ?? null,
                'billing_address_id' => $data['billing_address_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? $user?->name,
                'customer_phone' => $data['customer_phone'] ?? $user?->phone,
                'customer_email' => $data['customer_email'] ?? $user?->email,
                'notes' => $data['notes'] ?? null,
                'served_by' => $data['served_by'] ?? null,
                'created_by' => $data['created_by'] ?? $user?->id,
            ]);

            foreach ($lineItems as $line) {
                /** @var Product $product */
                $product = $line['product'];
                /** @var ProductVariant|null $variant */
                $variant = $line['variant'];

                $itemSubtotal = round($line['quantity'] * $line['unit_price'], 2);
                $itemTax = round($itemSubtotal * ($line['tax_rate'] / 100), 2);

                $orderItem = $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'warehouse_id' => $warehouse->id,
                    'product_name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => 0,
                    'tax' => $itemTax,
                    'subtotal' => $itemSubtotal,
                ]);

                $this->inventory->deduct(
                    product: $product,
                    variant: $variant,
                    warehouse: $warehouse,
                    quantity: $line['quantity'],
                    type: 'sale',
                    reference: $order,
                    note: "Order {$order->order_no} ({$source})",
                    userId: $data['created_by'] ?? $user?->id,
                );

                unset($orderItem);
            }

            if ($coupon) {
                $this->coupons->recordUsage($coupon, $order, $user, $discountAmount);
            }

            $order->statusHistories()->create([
                'status' => $order->status,
                'note' => 'Order created via '.$source,
                'changed_by' => $data['created_by'] ?? $user?->id,
            ]);

            event(new OrderPlaced($order->fresh(['items', 'warehouse', 'user'])));

            return $order;
        });
    }

    public function updateStatus(Order $order, string $status, ?string $note = null, ?int $changedBy = null): Order
    {
        $order->update(['status' => $status]);

        $order->statusHistories()->create([
            'status' => $status,
            'note' => $note,
            'changed_by' => $changedBy,
        ]);

        return $order->fresh();
    }

    public function cancelOrder(Order $order, string $reason, ?int $changedBy = null): Order
    {
        return DB::transaction(function () use ($order, $reason, $changedBy) {
            if (in_array($order->status, ['completed', 'delivered', 'cancelled'], true)) {
                abort(422, "Order cannot be cancelled from its current status ({$order->status}).");
            }

            foreach ($order->items as $item) {
                $this->inventory->add(
                    product: $item->product,
                    variant: $item->variant,
                    warehouse: $item->warehouse,
                    quantity: $item->quantity,
                    type: 'adjustment',
                    reference: $order,
                    note: "Cancelled order {$order->order_no}",
                    userId: $changedBy,
                );
            }

            $order->update(['status' => 'cancelled', 'cancelled_reason' => $reason]);

            $order->statusHistories()->create([
                'status' => 'cancelled',
                'note' => $reason,
                'changed_by' => $changedBy,
            ]);

            return $order->fresh();
        });
    }

    /**
     * @param  array<int, array{product_id: int, product_variant_id?: int|null, quantity: int}>  $items
     * @return Collection<int, array{product: Product, variant: ?ProductVariant, quantity: int, unit_price: float, tax_rate: float}>
     */
    private function resolveLineItems(array $items): Collection
    {
        return collect($items)->map(function (array $item) {
            $product = Product::query()->findOrFail($item['product_id']);
            $variant = ! empty($item['product_variant_id'])
                ? ProductVariant::query()->where('product_id', $product->id)->findOrFail($item['product_variant_id'])
                : null;

            $unitPrice = $variant?->selling_price ?? $product->currentPrice();

            return [
                'product' => $product,
                'variant' => $variant,
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $unitPrice,
                'tax_rate' => (float) $product->tax_rate,
            ];
        });
    }

    private function generateOrderNumber(string $source): string
    {
        $prefix = match ($source) {
            'website' => 'WEB',
            'facebook' => 'FB',
            'pos' => 'POS',
            default => 'ORD',
        };

        return "{$prefix}-".now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
