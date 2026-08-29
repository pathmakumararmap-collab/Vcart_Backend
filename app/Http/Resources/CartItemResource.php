<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $unitPrice = $this->variant?->selling_price ?? $this->product?->currentPrice();

        return [
            'id' => $this->id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'quantity' => $this->quantity,
            'unit_price' => (float) $unitPrice,
            'subtotal' => (float) ($unitPrice * $this->quantity),
        ];
    }
}
