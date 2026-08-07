<?php

declare(strict_types=1);

namespace App\Enum;

enum CarBodyStyle: string
{
    case COMPACT_01 = 'compact_01';
    case HATCH_01 = 'hatch_01';
    case COUPE_01 = 'coupe_01';
    case COUPE_02 = 'coupe_02';
    case MUSCLE_01 = 'muscle_01';
    case ROADSTER_01 = 'roadster_01';
    case SEDAN_01 = 'sedan_01';
    case RALLY_01 = 'rally_01';
    case RETRO_01 = 'retro_01';
    case SUPER_01 = 'super_01';

    public function label(): string
    {
        return match ($this) {
            self::COMPACT_01 => 'Compacte sportive',
            self::HATCH_01 => 'Hot hatch',
            self::COUPE_01 => 'Coupé sportif',
            self::COUPE_02 => 'Coupé tuner',
            self::MUSCLE_01 => 'Muscle',
            self::ROADSTER_01 => 'Roadster',
            self::SEDAN_01 => 'Berline sportive',
            self::RALLY_01 => 'Rallye',
            self::RETRO_01 => 'Coupé rétro',
            self::SUPER_01 => 'Supercar',
        };
    }
}
