<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keys_returned' => ['required', 'boolean'],
            'condition_notes' => ['nullable', 'string', 'max:10000'],
            'damages_amount' => ['required', 'decimal:0,2', 'min:0'],
        ];
    }
}
