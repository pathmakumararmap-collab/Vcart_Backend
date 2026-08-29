<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'adjustment_no' => $this->adjustment_no,
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'reason' => $this->reason,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => StockAdjustmentItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
