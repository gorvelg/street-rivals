<?php

declare(strict_types=1);

namespace App\Enum;

enum CardKind: string
{
    /**
     * Capacité passive ou conditionnelle
     * utilisée pendant les duels.
     */
    case ABILITY = 'ability';

    /**
     * Objet pouvant être installé sur la voiture.
     */
    case EQUIPMENT = 'equipment';

    /**
     * Bonus permanent de statistiques.
     */
    case STAT_BOOST = 'stat_boost';

    public function getLabel(): string
    {
        return match ($this) {
            self::ABILITY => 'Capacité',
            self::EQUIPMENT => 'Équipement',
            self::STAT_BOOST => 'Bonus permanent',
        };
    }

    public function requiresEquipmentSlot(): bool
    {
        return $this === self::EQUIPMENT;
    }
}
