<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'attributes' => $this->attributes,
            'cost_price' => (float) $this->cost_price,
            'selling_price' => (float) $this->selling_price,
            'image' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'is_active' => $this->is_active,
            'stock_quantity' => $this->whenLoaded('stocks', fn () => $this->stocks->sum('quantity')),
        ];
    }
}
