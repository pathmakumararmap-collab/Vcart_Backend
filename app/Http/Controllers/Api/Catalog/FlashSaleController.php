<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use OpenApi\Attributes as OA;

class FlashSaleController extends Controller
{
    /**
     * A "flash sale" is a product-scoped Coupon (see admin Flash Sale page)
     * that is currently active. This surfaces the linked products, each
     * priced at its flash-sale rate, plus the soonest end time across all
     * currently-running flash sales for a homepage countdown.
     */
    #[OA\Get(
        path: '/catalog/flash-sale',
        tags: ['Catalog'],
        summary: 'Currently active flash-sale products',
        responses: [new OA\Response(response: 200, description: 'Flash sale products and end time')],
    )]
    public function index(): JsonResponse
    {
        $coupons = Coupon::query()
            ->where('applicable_to', 'product')
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with(['products' => fn ($q) => $q->where('is_active', true)
                ->with(['category', 'brand', 'images'])
                ->with(['variants:id,product_id,selling_price,is_active'])
                ->withAvg('approvedReviews as reviews_avg_rating', 'rating')
                ->withCount('approvedReviews as reviews_count')])
            ->get();

        $earliestEnd = null;
        $best = [];

        foreach ($coupons as $coupon) {
            if ($coupon->expires_at instanceof Carbon && (! $earliestEnd || $coupon->expires_at->lt($earliestEnd))) {
                $earliestEnd = $coupon->expires_at;
            }

            foreach ($coupon->products as $product) {
                $price = $product->currentPrice();

                $flashPrice = $coupon->type === 'percentage'
                    ? $price * (1 - ((float) $coupon->value / 100))
                    : max(0, $price - (float) $coupon->value);

                if ($coupon->max_discount_amount !== null) {
                    $minPrice = $price - (float) $coupon->max_discount_amount;
                    $flashPrice = max($flashPrice, $minPrice);
                }

                $flashPrice = round(max(0, $flashPrice), 2);

                // A product can be in more than one active flash sale —
                // keep whichever gives the customer the better price.
                if (! isset($best[$product->id]) || $flashPrice < $best[$product->id]['price']) {
                    $best[$product->id] = ['product' => $product, 'price' => $flashPrice];
                }
            }
        }

        $products = collect($best)->values()->map(function (array $entry) {
            $product = $entry['product'];
            $original = $product->currentPrice();
            $flashPrice = $entry['price'];

            $payload = (new ProductResource($product))->resolve();
            $payload['flash_sale_price'] = $flashPrice;
            $payload['flash_sale_discount_percent'] = $original > 0
                ? (int) round((1 - $flashPrice / $original) * 100)
                : 0;

            return $payload;
        });

        return response()->json([
            'data' => [
                'ends_at' => $earliestEnd?->toIso8601String(),
                'products' => $products->values(),
            ],
        ]);
    }
}