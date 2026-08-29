<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'product_name' => $this->product_name,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount' => (float) $this->discount,
            'tax' => (float) $this->tax,
            'subtotal' => (float) $this->subtotal,
        ];
    }
}
