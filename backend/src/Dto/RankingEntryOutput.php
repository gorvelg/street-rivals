<?php

declare(strict_types=1);

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\RankingProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Ranking',
    operations: [
        new GetCollection(
            uriTemplate: '/rankings',
            security: "is_granted('ROLE_USER')",
            provider: RankingProvider::class,
            paginationEnabled: false,
            normalizationContext: [
                'groups' => ['ranking:read'],
            ],
        ),
    ],
)]
final readonly class RankingEntryOutput
{
    public function __construct(
        #[Groups(['ranking:read'])]
        public int $rank,

        #[Groups(['ranking:read'])]
        public int $carId,

        #[Groups(['ranking:read'])]
        public string $pilotName,

        #[Groups(['ranking:read'])]
        public string $color,

        #[Groups(['ranking:read'])]
        public int $level,

        #[Groups(['ranking:read'])]
        public int $rating,

        #[Groups(['ranking:read'])]
        public int $wins,

        #[Groups(['ranking:read'])]
        public int $losses,

        #[Groups(['ranking:read'])]
        public int $duelsPlayed,

        #[Groups(['ranking:read'])]
        public float $winRate,

        #[Groups(['ranking:read'])]
        public bool $isCurrentUser,
    ) {
    }
}
