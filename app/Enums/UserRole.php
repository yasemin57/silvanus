<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'ADMIN';
    case Operator = 'OPERATOR';
    case Firefighter = 'FIREFIGHTER';
    case FireChief = 'FIRE_CHIEF';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Sistem Yöneticisi',
            self::Operator => 'Operatör / Merkez Kontrol',
            self::Firefighter => 'İtfaiye Personeli',
            self::FireChief => 'İtfaiye Amiri',
        };
    }
}
