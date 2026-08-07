<?php

declare(strict_types=1);

namespace App\Enum;

enum CarStat: string
{
    case SPEED = 'speed';
    case ACCELERATION = 'acceleration';
    case GRIP = 'grip';
    case SOLIDITY = 'solidity';

    public function getLabel(): string
    {
        return match ($this) {
            self::SPEED => 'Vitesse',
            self::ACCELERATION => 'Accélération',
            self::GRIP => 'Adhérence',
            self::SOLIDITY => 'Solidité',
        };
    }
}
