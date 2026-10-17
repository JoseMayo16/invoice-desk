<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $balance = $this->route('invoice')->balance() / 100;

        return [
            'amount' => ['required', 'numeric', 'gt:0', "max:{$balance}", 'decimal:0,2'],
            'method' => ['required', Rule::enum(PaymentMethod::class)->except([PaymentMethod::Card])],
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
            'reference' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['amount.max' => 'The amount cannot be more than the balance due.'];
    }
}
