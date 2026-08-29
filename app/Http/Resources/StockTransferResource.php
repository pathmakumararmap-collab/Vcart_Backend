<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_no' => $this->transfer_no,
            'from_warehouse' => new WarehouseResource($this->whenLoaded('fromWarehouse')),
            'to_warehouse' => new WarehouseResource($this->whenLoaded('toWarehouse')),
            'status' => $this->status,
            'transfer_date' => $this->transfer_date?->toDateString(),
            'received_date' => $this->received_date?->toDateString(),
            'notes' => $this->notes,
            'items' => StockTransferItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
