<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('warehouses.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouseId)],
            'type' => ['sometimes', 'required', 'in:main,branch,outlet'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
