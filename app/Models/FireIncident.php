<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FireIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_code',
        'title',
        'zone_name',
        'latitude',
        'longitude',
        'altitude',
        'risk_score',
        'severity',
        'status',
        'detected_at',
        'primary_sensor_id',
        'primary_camera_id',
        'fire_station_id',
        'ai_confidence',
        'ai_analysis_summary',
        'weather_snapshot',
        'estimated_area_m2',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'altitude' => 'float',
            'risk_score' => 'float',
            'severity' => IncidentSeverity::class,
            'status' => IncidentStatus::class,
            'ai_confidence' => 'float',
            'estimated_area_m2' => 'float',
            'ai_analysis_summary' => 'array',
            'weather_snapshot' => 'array',
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function primarySensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class, 'primary_sensor_id');
    }

    public function primaryCamera(): BelongsTo
    {
        return $this->belongsTo(Camera::class, 'primary_camera_id');
    }

    public function fireStation(): BelongsTo
    {
        return $this->belongsTo(FireStation::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(IncidentTimeline::class)->orderBy('recorded_at', 'asc');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', IncidentStatus::Resolved);
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', IncidentSeverity::Critical);
    }

    public function isResolved(): bool
    {
        return $this->status === IncidentStatus::Resolved;
    }
}
