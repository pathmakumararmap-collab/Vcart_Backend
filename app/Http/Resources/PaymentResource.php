<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_no' => $this->payment_no,
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => $this->paymentMethod->name),
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'transaction_id' => $this->transaction_id,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'meta' => $this->meta,
        ];
    }
}