<?php

declare(strict_types=1);

namespace App\Model;

final readonly class DuelPolicyResult
{
    public function __construct(
        public int $dailyDuelNumber,
        public int $pairDuelNumber,
        public float $rewardMultiplier,
        public float $ratingMultiplier,
    ) {
    }
}
