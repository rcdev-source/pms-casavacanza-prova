<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:20'],
            'province' => ['required', 'string', 'size:2'],
            'country' => ['required', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'default_check_in_time' => ['required', 'date_format:H:i'],
            'default_check_out_time' => ['required', 'date_format:H:i'],
            'cancellation_policy' => ['nullable', 'string', 'max:10000'],
            'house_rules' => ['nullable', 'string', 'max:10000'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'timezone'],
        ];
    }
}
