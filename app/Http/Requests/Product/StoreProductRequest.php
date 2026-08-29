<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:selling_price'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'has_variants' => ['boolean'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'variants' => ['array'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:100', 'distinct'],
            'variants.*.barcode' => ['required_with:variants', 'string', 'max:100', 'distinct'],
            'variants.*.attributes' => ['required_with:variants', 'array'],
            'variants.*.cost_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.selling_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'max:4096'],
            'variants.*.initial_quantity' => ['nullable', 'integer', 'min:0'],
            'images' => ['array'],
            'images.*' => ['image', 'max:4096'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'initial_quantity' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
