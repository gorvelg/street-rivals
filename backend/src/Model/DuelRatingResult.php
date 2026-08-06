<?php

declare(strict_types=1);

namespace App\Model;

final readonly class DuelRatingResult
{
    public function __construct(
        public int $attackerBefore,
        public int $defenderBefore,
        public int $attackerAfter,
        public int $defenderAfter,
        public int $attackerDelta,
        public int $defenderDelta,
    ) {
    }
}
