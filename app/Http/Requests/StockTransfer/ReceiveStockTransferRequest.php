<?php

namespace App\Http\Requests\StockTransfer;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock-transfers.receive') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:stock_transfer_items,id'],
            'items.*.received_quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
