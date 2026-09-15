<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReservationChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'decimal:0,2', 'gt:0'],
            'unit_price' => ['required', 'decimal:0,2', 'min:0'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
