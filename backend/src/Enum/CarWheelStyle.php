<?php

declare(strict_types=1);

namespace App\Enum;

enum CarWheelStyle: string
{
    case STREET_01 = 'street_01';
    case FIVE_SPOKE = 'five_spoke';
    case MULTI_SPOKE = 'multi_spoke';

    public function label(): string
    {
        return match ($this) {
            self::STREET_01 =>
            'Street',

            self::FIVE_SPOKE =>
            '5 branches',

            self::MULTI_SPOKE =>
            'Multi-branches',
        };
    }
}
