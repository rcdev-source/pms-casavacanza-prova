<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PricingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
            'room_id' => ['nullable', 'ulid', 'exists:rooms,id'],
            'name' => ['required', 'string', 'max:120'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:1,7', 'distinct'],
            'price_per_night' => ['required', 'decimal:0,2', 'min:0'],
            'minimum_stay' => ['required', 'integer', 'min:1', 'max:365'],
            'priority' => ['sometimes', 'integer', 'between:-10000,10000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
