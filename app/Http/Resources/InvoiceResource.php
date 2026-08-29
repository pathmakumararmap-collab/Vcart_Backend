<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'download_url' => route('invoices.download', $this->id),
        ];
    }
}
