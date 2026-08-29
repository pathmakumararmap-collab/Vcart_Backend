<?php

namespace App\Http\Requests\Damage;

use Illuminate\Foundation\Http\FormRequest;

class StoreDamageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('damages.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.estimated_loss' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
