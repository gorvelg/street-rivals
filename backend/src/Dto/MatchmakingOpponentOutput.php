<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class MatchmakingOpponentOutput
{
    /**
     * @param array{
     *     speed: int,
     *     acceleration: int,
     *     grip: int,
     *     solidity: int
     * } $effectiveStats
     *
     * @param array{
     *     victory: array{xp: int, money: int},
     *     defeat: array{xp: int, money: int}
     * } $potentialRewards
     */
    public function __construct(
        #[Groups(['matchmaking:read'])]
        public int $carId,

        #[Groups(['matchmaking:read'])]
        public string $pilotName,

        #[Groups(['matchmaking:read'])]
        public string $color,

        #[Groups(['matchmaking:read'])]
        public string $bodyStyle,

        #[Groups(['matchmaking:read'])]
        public string $wheelStyle,

        #[Groups(['matchmaking:read'])]
        public int $level,

        #[Groups(['matchmaking:read'])]
        public int $levelDifference,

        #[Groups(['matchmaking:read'])]
        public array $effectiveStats,

        #[Groups(['matchmaking:read'])]
        public int $powerScore,

        #[Groups(['matchmaking:read'])]
        public float $powerDifferencePercent,

        #[Groups(['matchmaking:read'])]
        public string $difficulty,

        #[Groups(['matchmaking:read'])]
        public array $potentialRewards,
    ) {
    }
}
