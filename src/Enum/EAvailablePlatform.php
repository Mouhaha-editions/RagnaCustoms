<?php

namespace App\Enum;

enum EAvailablePlatform: int
{
    case VR = 0;
    case Flat = 1;

    public static function choices(): array
    {
        return [
            'VR'   => self::VR,
            'Flat' => self::Flat
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::VR => 'VR',
            self::Flat => 'Flat'
        };
    }
}
