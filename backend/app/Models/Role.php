<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory, HasUlids;

    public const ADMIN = 'ADMIN';
    public const RECEPTION = 'RECEPTION';
    public const CLEANING = 'CLEANING';
    public const MAINTENANCE = 'MAINTENANCE';

    protected $fillable = ['key', 'name'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
