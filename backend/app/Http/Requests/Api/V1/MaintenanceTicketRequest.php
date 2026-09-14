<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\MaintenancePriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'ulid', 'exists:rooms,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::enum(MaintenancePriority::class)],
            'blocks_room' => ['required', 'boolean'],
            'assigned_to' => ['nullable', 'ulid', 'exists:users,id'],
        ];
    }
}
