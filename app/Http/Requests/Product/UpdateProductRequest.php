<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'cost_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'has_variants' => ['boolean'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],

            'images' => ['sometimes', 'array'],
            'images.*' => ['image', 'max:4096'],

            'variants' => ['sometimes', 'array'],
            'variants.*.id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $productId)],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.attributes' => ['required_with:variants', 'array'],
            'variants.*.cost_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.selling_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
