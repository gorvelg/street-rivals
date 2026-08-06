<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Model\DuelRatingResult;

final class RatingCalculator
{
    private const K_FACTOR = 32;
    private const RATING_SCALE = 400;

    public function calculate(
        Car $attacker,
        Car $defender,
        Car $winner,
        float $multiplier = 1.0,
    ): DuelRatingResult {
        if ($multiplier < 0 || $multiplier > 1) {
            throw new \InvalidArgumentException(
                'Le multiplicateur Elo doit être compris entre zéro et un.'
            );
        }

        $attackerId = $attacker->getId();
        $defenderId = $defender->getId();
        $winnerId = $winner->getId();

        if (
            $attackerId === null
            || $defenderId === null
            || $winnerId === null
        ) {
            throw new \LogicException(
                'Les voitures doivent être enregistrées.'
            );
        }

        if (
            $winnerId !== $attackerId
            && $winnerId !== $defenderId
        ) {
            throw new \LogicException(
                'La voiture gagnante ne participe pas au duel.'
            );
        }

        $attackerBefore = $attacker->getRating();
        $defenderBefore = $defender->getRating();

        $expectedAttacker = 1 / (
                1 + 10 ** (
                    ($defenderBefore - $attackerBefore)
                    / self::RATING_SCALE
                )
            );

        $attackerWon = $winnerId === $attackerId;

        $baseAttackerDelta = $attackerWon
            ? max(
                1,
                (int) round(
                    self::K_FACTOR
                    * (1 - $expectedAttacker)
                )
            )
            : -max(
                1,
                (int) round(
                    self::K_FACTOR
                    * $expectedAttacker
                )
            );

        if ($multiplier === 0.0) {
            $attackerDelta = 0;
        } else {
            $scaledAbsoluteDelta = max(
                1,
                (int) round(
                    abs($baseAttackerDelta) * $multiplier
                )
            );

            $attackerDelta = $baseAttackerDelta > 0
                ? $scaledAbsoluteDelta
                : -$scaledAbsoluteDelta;
        }

        $defenderDelta = -$attackerDelta;

        return new DuelRatingResult(
            attackerBefore: $attackerBefore,
            defenderBefore: $defenderBefore,
            attackerAfter:
            $attackerBefore + $attackerDelta,
            defenderAfter:
            $defenderBefore + $defenderDelta,
            attackerDelta: $attackerDelta,
            defenderDelta: $defenderDelta,
        );
    }
}
