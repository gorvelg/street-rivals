<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Car;

final readonly class DuelSimulationResult
{
    /**
     * @param array<string, mixed> $attackerSnapshot
     * @param array<string, mixed> $defenderSnapshot
     * @param array<string, mixed> $replayData
     */
    public function __construct(
        public Car $winnerCar,
        public int $finalGap,
        public string $randomSeed,
        public string $engineVersion,
        public array $attackerSnapshot,
        public array $defenderSnapshot,
        public array $replayData,
    ) {
    }
}
