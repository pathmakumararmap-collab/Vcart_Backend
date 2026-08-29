<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->availableQuantity(),
        ];
    }
}
