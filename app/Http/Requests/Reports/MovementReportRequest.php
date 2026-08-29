<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class MovementReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'warehouse_type' => ['nullable', 'in:main,branch,outlet'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'group_by' => ['nullable', 'in:category,subcategory,brand'],
            'bucket' => ['nullable', 'in:fast,slow,non_moving'],
            'format' => ['nullable', 'in:excel,pdf,word'],
        ];
    }
}
