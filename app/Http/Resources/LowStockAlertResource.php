<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LowStockAlertResource extends JsonResource
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
            'threshold' => $this->threshold,
            'current_quantity' => $this->current_quantity,
            'status' => $this->status,
            'notified_at' => $this->notified_at?->toIso8601String(),
        ];
    }
}
