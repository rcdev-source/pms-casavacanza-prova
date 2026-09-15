<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreCheckInToken extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'reservation_id',
        'token_hash',
        'expires_at',
        'used_at',
        'payload',
        'created_by',
    ];

    protected $hidden = ['token_hash', 'payload'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'payload' => 'encrypted:array',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
