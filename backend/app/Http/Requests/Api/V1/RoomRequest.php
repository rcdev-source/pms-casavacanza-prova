<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\RoomStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roomId = $this->route('room')?->id;

        return [
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('rooms')->where('property_id', $this->input('property_id'))->ignore($roomId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'max_guests' => ['required', 'integer', 'min:1', 'max:50'],
            'max_adults' => ['required', 'integer', 'min:1', 'lte:max_guests'],
            'max_children' => ['required', 'integer', 'min:0', 'lte:max_guests'],
            'base_price' => ['required', 'decimal:0,2', 'min:0'],
            'status' => ['required', Rule::enum(RoomStatus::class)],
            'is_active' => ['required', 'boolean'],
            'amenity_ids' => ['sometimes', 'array'],
            'amenity_ids.*' => ['ulid', 'exists:room_amenities,id'],
        ];
    }
}
