<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'cost_price' => $this->when(
                $request->user()?->can('products.viewCost'),
                fn () => (float) $this->cost_price,
            ),
            'selling_price' => (float) $this->selling_price,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,
            'current_price' => (float) $this->currentPrice(),
            'tax_rate' => (float) $this->tax_rate,
            'unit' => $this->unit,
            'has_variants' => $this->has_variants,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'low_stock_threshold' => $this->low_stock_threshold,
            'weight' => $this->weight ? (float) $this->weight : null,
            'total_stock' => $this->when($this->relationLoaded('stocks'), fn () => $this->stocks->sum('quantity')),
            'rating_average' => $this->when(
                $this->reviews_avg_rating !== null,
                fn () => round((float) $this->reviews_avg_rating, 1)
            ),
            'reviews_count' => $this->when($this->reviews_count !== null, fn () => (int) $this->reviews_count),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
