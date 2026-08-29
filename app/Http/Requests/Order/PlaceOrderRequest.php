<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'shipping_address_id' => ['required_without:customer_name', 'nullable', 'exists:addresses,id'],
            'billing_address_id' => ['nullable', 'exists:addresses,id'],
            'customer_name' => ['required_without:shipping_address_id', 'nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'channel_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
