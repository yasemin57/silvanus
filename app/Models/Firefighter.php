<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Firefighter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fire_station_id',
        'badge_number',
        'rank',
        'phone',
        'status',
        'fcm_device_token',
        'last_known_latitude',
        'last_known_longitude',
        'last_location_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'last_known_latitude' => 'float',
            'last_known_longitude' => 'float',
            'last_location_updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fireStation(): BelongsTo
    {
        return $this->belongsTo(FireStation::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class);
    }
}
