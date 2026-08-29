<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class PosCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pos.sell') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'user_id' => ['nullable', 'exists:users,id'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
