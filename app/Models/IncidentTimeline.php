<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentTimeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'fire_incident_id',
        'user_id',
        'status_from',
        'status_to',
        'action_title',
        'note',
        'latitude',
        'longitude',
        'duration_from_previous_seconds',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'duration_from_previous_seconds' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(FireIncident::class, 'fire_incident_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
