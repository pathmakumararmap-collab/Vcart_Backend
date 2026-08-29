<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('orders.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,confirmed,processing,packed,shipped,delivered,completed,cancelled,returned'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
