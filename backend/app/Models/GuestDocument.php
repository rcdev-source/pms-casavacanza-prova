<?php

namespace App\Models;

use App\Enums\GuestDocumentType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestDocument extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'guest_id',
        'document_type',
        'document_number',
        'issue_date',
        'expiry_date',
        'issuing_country',
        'file_path',
        'verified_at',
    ];

    protected $hidden = ['file_path'];

    protected function casts(): array
    {
        return [
            'document_type' => GuestDocumentType::class,
            'document_number' => 'encrypted',
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
