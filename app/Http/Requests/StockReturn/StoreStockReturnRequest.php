<?php

namespace App\Http\Requests\StockReturn;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock-returns.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['nullable', 'exists:orders,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'type' => ['required', 'in:customer_return,supplier_return'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.condition' => ['required', 'in:good,damaged'],
        ];
    }
}
