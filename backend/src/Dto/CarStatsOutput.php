<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

final class CarStatsOutput
{
    /**
     * @param array{
     *     speed: int,
     *     acceleration: int,
     *     grip: int,
     *     solidity: int
     * } $base
     *
     * @param array{
     *     speed: int,
     *     acceleration: int,
     *     grip: int,
     *     solidity: int
     * } $bonuses
     *
     * @param array{
     *     speed: int,
     *     acceleration: int,
     *     grip: int,
     *     solidity: int
     * } $effective
     *
     * @param list<array{
     *     carCardId: int|null,
     *     cardId: int|null,
     *     code: string|null,
     *     name: string|null,
     *     tier: int,
     *     stat: string,
     *     value: int
     * }> $appliedCards
     */
    public function __construct(
        #[Groups(['car-stats:read'])]
        public readonly int $carId,

        #[Groups(['car-stats:read'])]
        public readonly string $pilotName,

        #[Groups(['car-stats:read'])]
        public readonly int $level,

        #[Groups(['car-stats:read'])]
        public readonly array $base,

        #[Groups(['car-stats:read'])]
        public readonly array $bonuses,

        #[Groups(['car-stats:read'])]
        public readonly array $effective,

        #[Groups(['car-stats:read'])]
        public readonly array $appliedCards,
    ) {
    }
}
