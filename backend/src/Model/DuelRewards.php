<?php

declare(strict_types=1);

namespace App\Model;

final readonly class DuelRewards
{
    public function __construct(
        public int $attackerXp,
        public int $attackerMoney,
        public int $defenderXp,
        public int $defenderMoney,
    ) {
    }
}
