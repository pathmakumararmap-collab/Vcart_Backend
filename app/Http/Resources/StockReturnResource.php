<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockReturnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_no' => $this->return_no,
            'order_id' => $this->order_id,
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'type' => $this->type,
            'status' => $this->status,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'items' => StockReturnItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
