<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
