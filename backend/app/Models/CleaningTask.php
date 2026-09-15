<?php

namespace App\Models;

use App\Enums\CleaningTaskStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CleaningTask extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'property_id',
        'room_id',
        'reservation_id',
        'check_out_id',
        'scheduled_for',
        'status',
        'priority',
        'assigned_to',
        'started_at',
        'completed_at',
        'notes',
        'completion_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'status' => CleaningTaskStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function checkOut(): BelongsTo
    {
        return $this->belongsTo(CheckOut::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
