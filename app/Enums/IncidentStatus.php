<?php

declare(strict_types=1);

namespace App\Enums;

enum IncidentStatus: string
{
    case Detected = 'DETECTED';
    case Notified = 'NOTIFIED';
    case Accepted = 'ACCEPTED';
    case OnTheWay = 'ON_THE_WAY';
    case Arrived = 'ARRIVED';
    case UnderControl = 'UNDER_CONTROL';
    case Resolved = 'RESOLVED';

    public function label(): string
    {
        return match ($this) {
            self::Detected => 'Tespit Edildi',
            self::Notified => 'Bildirildi',
            self::Accepted => 'Kabul Edildi',
            self::OnTheWay => 'Yola Çıkıldı',
            self::Arrived => 'Olay Yerine Ulaşıldı',
            self::UnderControl => 'Kontrol Altında',
            self::Resolved => 'Tamamlandı',
        };
    }

    public function stepOrder(): int
    {
        return match ($this) {
            self::Detected => 1,
            self::Notified => 2,
            self::Accepted => 3,
            self::OnTheWay => 4,
            self::Arrived => 5,
            self::UnderControl => 6,
            self::Resolved => 7,
        };
    }

    public function nextStatus(): ?self
    {
        return match ($this) {
            self::Detected => self::Notified,
            self::Notified => self::Accepted,
            self::Accepted => self::OnTheWay,
            self::OnTheWay => self::Arrived,
            self::Arrived => self::UnderControl,
            self::UnderControl => self::Resolved,
            self::Resolved => null,
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Resolved;
    }
}
