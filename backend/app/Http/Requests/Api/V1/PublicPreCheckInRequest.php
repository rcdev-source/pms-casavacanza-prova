<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\GuestDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicPreCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before:today'],
            'birth_place' => ['required', 'string', 'max:120'],
            'nationality' => ['required', 'string', 'size:2'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'document_type' => ['required', Rule::enum(GuestDocumentType::class)],
            'document_number' => ['required', 'string', 'max:120'],
            'document_issuing_country' => ['required', 'string', 'size:2'],
            'document_expiry_date' => ['required', 'date_format:Y-m-d', 'after:today'],
        ];
    }
}
