<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckOut extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'property_id',
        'reservation_id',
        'completed_at',
        'completed_by',
        'keys_returned',
        'condition_notes',
        'damages_amount',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'keys_returned' => 'boolean',
            'damages_amount' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
