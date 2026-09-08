<?php

declare(strict_types=1);

namespace App\Enums;

enum IncidentSeverity: string
{
    case Low = 'LOW';
    case Medium = 'MEDIUM';
    case High = 'HIGH';
    case Critical = 'CRITICAL';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Düşük',
            self::Medium => 'Orta',
            self::High => 'Yüksek',
            self::Critical => 'Kritik',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Low => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
            self::Medium => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
            self::High => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
            self::Critical => 'bg-rose-500/20 text-rose-400 border-rose-500/30 animate-pulse',
        };
    }
}
