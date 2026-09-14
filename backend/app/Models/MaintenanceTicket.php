<?php

namespace App\Models;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTicket extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'property_id',
        'room_id',
        'title',
        'description',
        'status',
        'priority',
        'blocks_room',
        'availability_block_id',
        'assigned_to',
        'reported_by',
        'started_at',
        'resolved_at',
        'closed_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => MaintenanceStatus::class,
            'priority' => MaintenancePriority::class,
            'blocks_room' => 'boolean',
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function availabilityBlock(): BelongsTo
    {
        return $this->belongsTo(AvailabilityBlock::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
