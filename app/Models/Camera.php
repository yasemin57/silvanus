<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Camera extends Model
{
    use HasFactory;

    protected $fillable = [
        'camera_code',
        'name',
        'zone_name',
        'latitude',
        'longitude',
        'altitude',
        'stream_url',
        'snapshot_url',
        'fov_degrees',
        'orientation_degrees',
        'resolution',
        'has_thermal',
        'status',
        'last_ping_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'altitude' => 'float',
            'fov_degrees' => 'integer',
            'orientation_degrees' => 'integer',
            'has_thermal' => 'boolean',
            'status' => DeviceStatus::class,
            'last_ping_at' => 'datetime',
        ];
    }

    public function fireIncidents(): HasMany
    {
        return $this->hasMany(FireIncident::class, 'primary_camera_id');
    }

    public function isOnline(): bool
    {
        return $this->status === DeviceStatus::Active;
    }
}
