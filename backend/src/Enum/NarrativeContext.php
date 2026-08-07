<?php

declare(strict_types=1);

namespace App\Enum;

enum NarrativeContext: string
{
    case ANY = 'any';
    case START = 'start';
    case STRAIGHT = 'straight';
    case TURN = 'turn';
    case BRAKING = 'braking';
    case ACCELERATION = 'acceleration';

    public function label(): string
    {
        return match ($this) {
            self::ANY => 'Tous les contextes',
            self::START => 'Départ',
            self::STRAIGHT => 'Ligne droite',
            self::TURN => 'Virage',
            self::BRAKING => 'Freinage',
            self::ACCELERATION => 'Relance',
        };
    }
}
