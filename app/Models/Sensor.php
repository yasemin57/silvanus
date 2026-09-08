<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sensor extends Model
{
    use HasFactory;

    protected $fillable = [
        'sensor_code',
        'name',
        'zone_name',
        'latitude',
        'longitude',
        'altitude',
        'model',
        'battery_level',
        'status',
        'installed_at',
        'last_ping_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'altitude' => 'float',
            'battery_level' => 'integer',
            'status' => DeviceStatus::class,
            'installed_at' => 'datetime',
            'last_ping_at' => 'datetime',
        ];
    }

    public function readings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function latestReading(): HasOne
    {
        return $this->hasOne(SensorReading::class)->latestOfMany('recorded_at');
    }

    public function fireIncidents(): HasMany
    {
        return $this->hasMany(FireIncident::class, 'primary_sensor_id');
    }

    public function isOnline(): bool
    {
        return $this->status === DeviceStatus::Active;
    }
}
