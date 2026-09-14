<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CleaningTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'ulid', 'exists:rooms,id'],
            'scheduled_for' => ['required', 'date'],
            'priority' => ['required', 'integer', 'between:1,5'],
            'assigned_to' => ['nullable', 'ulid', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
