<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_no' => $this->purchase_no,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'status' => $this->status,
            'order_date' => $this->order_date?->toDateString(),
            'expected_date' => $this->expected_date?->toDateString(),
            'received_date' => $this->received_date?->toDateString(),
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'notes' => $this->notes,
            'items' => PurchaseItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
