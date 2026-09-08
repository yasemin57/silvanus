<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FireStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'latitude',
        'longitude',
        'altitude',
        'address',
        'phone',
        'coverage_radius_km',
        'active_units_count',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'altitude' => 'float',
            'coverage_radius_km' => 'float',
            'active_units_count' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function firefighters(): HasMany
    {
        return $this->hasMany(Firefighter::class);
    }

    public function fireIncidents(): HasMany
    {
        return $this->hasMany(FireIncident::class);
    }

    public function incidentAssignments(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class);
    }
}
