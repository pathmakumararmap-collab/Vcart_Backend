<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('coupons.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($couponId)],
            'type' => ['sometimes', 'required', 'in:fixed,percentage'],
            'value' => ['sometimes', 'required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'applicable_to' => ['sometimes', 'required', 'in:all,category,product'],
            'product_ids' => ['array'],
            'product_ids.*' => ['exists:products,id'],
            'category_ids' => ['array'],
            'category_ids.*' => ['exists:categories,id'],
        ];
    }
}
