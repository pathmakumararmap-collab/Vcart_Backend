<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'type' => $this->type,
            'quantity_change' => $this->quantity_change,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'reference_type' => $this->reference_type ? class_basename($this->reference_type) : null,
            'reference_id' => $this->reference_id,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
