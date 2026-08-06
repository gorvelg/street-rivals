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
    ): DuelRewards {
        if (
            $winner->getId() !== $attacker->getId()
            && $winner->getId() !== $defender->getId()
        ) {
            throw new \LogicException(
                'La voiture gagnante ne participe pas au duel.'
            );
        }

        $attackerWon = $winner->getId() === $attacker->getId();

        return new DuelRewards(
            attackerXp: $attackerWon
                ? self::WINNER_XP
                : self::LOSER_XP,

            attackerMoney: $attackerWon
                ? self::WINNER_MONEY
                : self::LOSER_MONEY,

            defenderXp: $attackerWon
                ? self::LOSER_XP
                : self::WINNER_XP,

            defenderMoney: $attackerWon
                ? self::LOSER_MONEY
                : self::WINNER_MONEY,
        );
    }
}
