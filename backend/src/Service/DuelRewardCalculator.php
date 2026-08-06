<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Model\DuelRewards;

final class DuelRewardCalculator
{
    public const WINNER_XP = 50;
    public const WINNER_MONEY = 100;

    public const LOSER_XP = 20;
    public const LOSER_MONEY = 25;

    public function calculate(
        Car $attacker,
        Car $defender,
        Car $winner,
        float $multiplier = 1.0,
    ): DuelRewards {
        if ($multiplier <= 0 || $multiplier > 1) {
            throw new \InvalidArgumentException(
                'Le multiplicateur de récompense doit être supérieur à zéro et inférieur ou égal à un.'
            );
        }

        if (
            $winner->getId() !== $attacker->getId()
            && $winner->getId() !== $defender->getId()
        ) {
            throw new \LogicException(
                'La voiture gagnante ne participe pas au duel.'
            );
        }

        $attackerWon =
            $winner->getId() === $attacker->getId();

        $attackerBaseXp = $attackerWon
            ? self::WINNER_XP
            : self::LOSER_XP;

        $attackerBaseMoney = $attackerWon
            ? self::WINNER_MONEY
            : self::LOSER_MONEY;

        $defenderBaseXp = $attackerWon
            ? self::LOSER_XP
            : self::WINNER_XP;

        $defenderBaseMoney = $attackerWon
            ? self::LOSER_MONEY
            : self::WINNER_MONEY;

        return new DuelRewards(
            attackerXp: $this->applyMultiplier(
                $attackerBaseXp,
                $multiplier
            ),
            attackerMoney: $this->applyMultiplier(
                $attackerBaseMoney,
                $multiplier
            ),
            defenderXp: $this->applyMultiplier(
                $defenderBaseXp,
                $multiplier
            ),
            defenderMoney: $this->applyMultiplier(
                $defenderBaseMoney,
                $multiplier
            ),
        );
    }

    private function applyMultiplier(
        int $value,
        float $multiplier,
    ): int {
        return max(
            1,
            (int) round($value * $multiplier)
        );
    }
}
