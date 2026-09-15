<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'status' => ['sometimes', Rule::in([
                PaymentStatus::PENDING->value,
                PaymentStatus::COMPLETED->value,
                PaymentStatus::FAILED->value,
            ])],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
