<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.receive') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:purchase_items,id'],
            'items.*.received_quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
