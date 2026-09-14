<?php

namespace App\Models;

use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reservation extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'property_id',
        'room_id',
        'booking_code',
        'primary_guest_id',
        'check_in_date',
        'check_out_date',
        'adults',
        'children',
        'status',
        'source',
        'currency',
        'subtotal',
        'discount',
        'taxes',
        'extras_total',
        'total',
        'deposit_required',
        'deposit_amount',
        'balance_due',
        'notes',
        'internal_notes',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'status' => ReservationStatus::class,
            'source' => ReservationSource::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'taxes' => 'decimal:2',
            'extras_total' => 'decimal:2',
            'total' => 'decimal:2',
            'deposit_required' => 'boolean',
            'deposit_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function primaryGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'primary_guest_id');
    }

    public function guests(): BelongsToMany
    {
        return $this->belongsToMany(Guest::class)->withPivot('is_primary')->withTimestamps();
    }
}
