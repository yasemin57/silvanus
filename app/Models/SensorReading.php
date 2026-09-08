<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'sensor_id',
        'temperature',
        'humidity',
        'smoke_ppm',
        'co_gas_ppm',
        'air_quality_index',
        'flame_raw_value',
        'raw_payload',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'humidity' => 'float',
            'smoke_ppm' => 'float',
            'co_gas_ppm' => 'float',
            'air_quality_index' => 'integer',
            'flame_raw_value' => 'float',
            'raw_payload' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }
}
