<?php

declare(strict_types=1);

namespace App\Enum;

enum NarrativePhraseType: string
{
    case ACTIVATION = 'activation';
    case EFFECT = 'effect';
    case REACTION = 'reaction';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVATION => 'Activation',
            self::EFFECT => 'Effet',
            self::REACTION => 'Réaction adverse',
        };
    }
}
