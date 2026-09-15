<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keys_delivered' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
