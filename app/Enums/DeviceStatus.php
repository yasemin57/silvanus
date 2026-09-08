<?php

declare(strict_types=1);

namespace App\Enums;

enum DeviceStatus: string
{
    case Active = 'ACTIVE';
    case Warning = 'WARNING';
    case Offline = 'OFFLINE';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Warning => 'Uyarı',
            self::Offline => 'Çevrimdışı',
        };
    }

    public function statusColor(): string
    {
        return match ($this) {
            self::Active => 'text-emerald-400 bg-emerald-500/20 border-emerald-500/30',
            self::Warning => 'text-amber-400 bg-amber-500/20 border-amber-500/30',
            self::Offline => 'text-rose-400 bg-rose-500/20 border-rose-500/30',
        };
    }
}
