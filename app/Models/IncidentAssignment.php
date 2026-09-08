<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'fire_incident_id',
        'firefighter_id',
        'fire_station_id',
        'status',
        'assigned_at',
        'accepted_at',
        'departed_at',
        'arrived_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'accepted_at' => 'datetime',
            'departed_at' => 'datetime',
            'arrived_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(FireIncident::class, 'fire_incident_id');
    }

    public function firefighter(): BelongsTo
    {
        return $this->belongsTo(Firefighter::class);
    }

    public function fireStation(): BelongsTo
    {
        return $this->belongsTo(FireStation::class);
    }
}
