<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'birth_place',
        'nationality',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class)->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(GuestDocument::class);
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class)->withPivot('is_primary')->withTimestamps();
    }
}
