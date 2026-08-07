<?php

declare(strict_types=1);

namespace App\Enum;

enum EquipmentSlot: string
{
    case ENGINE = 'engine';
    case WHEELS = 'wheels';
    case BRAKES = 'brakes';
    case GEARBOX = 'gearbox';
    case CHASSIS = 'chassis';
    case AERO = 'aero';

    public function getLabel(): string
    {
        return match ($this) {
            self::ENGINE => 'Moteur',
            self::WHEELS => 'Roues',
            self::BRAKES => 'Freins',
            self::GEARBOX => 'Boîte de vitesses',
            self::CHASSIS => 'Châssis',
            self::AERO => 'Aérodynamique',
        };
    }
}
