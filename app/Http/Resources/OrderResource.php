<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'source' => $this->source,
            'channel_reference' => $this->channel_reference,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'shipping_amount' => (float) $this->shipping_amount,
            'total_amount' => (float) $this->total_amount,
            'paid_amount' => (float) $this->paid_amount,
            'currency' => $this->currency,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'notes' => $this->notes,
            'cancelled_reason' => $this->cancelled_reason,
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'user' => new UserResource($this->whenLoaded('user')),
            'shipping_address' => new AddressResource($this->whenLoaded('shippingAddress')),
            'billing_address' => new AddressResource($this->whenLoaded('billingAddress')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'status_histories' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistories')),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
