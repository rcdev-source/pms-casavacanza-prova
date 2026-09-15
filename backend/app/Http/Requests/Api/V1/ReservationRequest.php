<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'ulid', 'exists:rooms,id'],
            'primary_guest_id' => ['required', 'ulid', 'exists:guests,id'],
            'guest_ids' => ['sometimes', 'array'],
            'guest_ids.*' => ['ulid', 'distinct', 'exists:guests,id'],
            'check_in_date' => ['required', 'date_format:Y-m-d'],
            'check_out_date' => ['required', 'date_format:Y-m-d', 'after:check_in_date'],
            'adults' => ['required', 'integer', 'min:1', 'max:50'],
            'children' => ['required', 'integer', 'min:0', 'max:50'],
            'status' => ['sometimes', Rule::in([
                ReservationStatus::DRAFT->value,
                ReservationStatus::REQUESTED->value,
                ReservationStatus::CONFIRMED->value,
            ])],
            'source' => ['sometimes', Rule::enum(ReservationSource::class)],
            'discount' => ['sometimes', 'decimal:0,2', 'min:0'],
            'taxes' => ['sometimes', 'decimal:0,2', 'min:0'],
            'deposit_required' => ['sometimes', 'boolean'],
            'deposit_amount' => ['sometimes', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
