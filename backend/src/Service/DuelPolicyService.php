<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Enum\GameSettingKey;
use App\Exception\DuelLimitException;
use App\Model\DuelPolicyResult;
use App\Repository\DuelRepository;

final class DuelPolicyService
{
    public function __construct(
        private readonly DuelRepository $duelRepository,
        private readonly GameSettingManager $gameSettingManager,
    ) {
    }

    public function assertCanStart(
        Car $attacker,
        Car $defender,
        ?\DateTimeImmutable $now = null,
    ): DuelPolicyResult {
        $utcTimezone = new \DateTimeZone('UTC');

        $now ??= new \DateTimeImmutable(
            'now',
            $utcTimezone,
        );

        $nowUtc = $now->setTimezone(
            $utcTimezone,
        );

        $dayStart = $nowUtc->setTime(
            0,
            0,
            0,
        );

        /*
         * Valeurs dynamiques provenant de game_setting.
         */
        $dailyAttackLimit =
            $this->getDailyAttackLimit();

        $pairDailyLimit =
            $this->getPairDailyLimit();

        $pairCooldownSeconds =
            $this->getPairCooldownSeconds();

        /*
         * Nombre de duels initiés aujourd’hui
         * par la voiture attaquante.
         */
        $dailyDuelCount = $this->duelRepository
            ->countInitiatedByCarSince(
                attacker: $attacker,
                since: $dayStart,
            );

        if (
            $dailyDuelCount
            >= $dailyAttackLimit
        ) {
            throw new DuelLimitException(
                message: sprintf(
                    'Cette voiture a déjà lancé %d duel(s) aujourd’hui.',
                    $dailyAttackLimit,
                ),
                retryAfterSeconds:
                $this->secondsUntilNextUtcDay(
                    $nowUtc,
                ),
            );
        }

        /*
         * Nombre de duels entre les deux voitures
         * depuis le début de la journée UTC.
         */
        $pairDuelCount = $this->duelRepository
            ->countBetweenCarsSince(
                firstCar: $attacker,
                secondCar: $defender,
                since: $dayStart,
            );

        if (
            $pairDuelCount
            >= $pairDailyLimit
        ) {
            throw new DuelLimitException(
                message: sprintf(
                    'Ces deux voitures se sont déjà affrontées %d fois aujourd’hui.',
                    $pairDailyLimit,
                ),
                retryAfterSeconds:
                $this->secondsUntilNextUtcDay(
                    $nowUtc,
                ),
            );
        }

        /*
         * Le cooldown peut être désactivé depuis
         * l’administration en utilisant la valeur 0.
         */
        if ($pairCooldownSeconds > 0) {
            $latestDuel = $this->duelRepository
                ->findLatestBetweenCars(
                    firstCar: $attacker,
                    secondCar: $defender,
                );

            if ($latestDuel !== null) {
                $nextAvailableAt = $latestDuel
                    ->getCreatedAt()
                    ->modify(
                        sprintf(
                            '+%d seconds',
                            $pairCooldownSeconds,
                        ),
                    );

                if ($nowUtc < $nextAvailableAt) {
                    $retryAfterSeconds = max(
                        1,
                        $nextAvailableAt
                            ->getTimestamp()
                        - $nowUtc->getTimestamp(),
                    );

                    throw new DuelLimitException(
                        message: sprintf(
                            'Ces voitures doivent attendre encore %d seconde(s) avant de s’affronter à nouveau.',
                            $retryAfterSeconds,
                        ),
                        retryAfterSeconds:
                        $retryAfterSeconds,
                    );
                }
            }
        }

        $dailyDuelNumber =
            $dailyDuelCount + 1;

        $pairDuelNumber =
            $pairDuelCount + 1;

        return new DuelPolicyResult(
            dailyDuelNumber:
            $dailyDuelNumber,

            pairDuelNumber:
            $pairDuelNumber,

            rewardMultiplier:
            $this->resolveRewardMultiplier(
                $pairDuelNumber,
            ),

            ratingMultiplier:
            $this->resolveRatingMultiplier(
                $pairDuelNumber,
            ),
        );
    }

    /**
     * Nombre maximal de duels qu’une voiture
     * peut lancer pendant une journée UTC.
     */
    public function getDailyAttackLimit(): int
    {
        return $this->gameSettingManager->getInt(
            GameSettingKey::
            DUEL_DAILY_ATTACK_LIMIT,
        );
    }

    /**
     * Nombre maximal de duels autorisés
     * quotidiennement entre les deux mêmes voitures.
     */
    public function getPairDailyLimit(): int
    {
        return $this->gameSettingManager->getInt(
            GameSettingKey::
            DUEL_PAIR_DAILY_LIMIT,
        );
    }

    /**
     * Délai minimal entre deux duels impliquant
     * la même paire de voitures.
     *
     * La valeur 0 désactive le cooldown.
     */
    public function getPairCooldownSeconds(): int
    {
        return $this->gameSettingManager->getInt(
            GameSettingKey::
            DUEL_PAIR_COOLDOWN_SECONDS,
        );
    }

    /**
     * Réduction progressive des récompenses
     * lorsque les mêmes voitures s’affrontent
     * plusieurs fois dans la même journée.
     */
    private function resolveRewardMultiplier(
        int $pairDuelNumber,
    ): float {
        return match ($pairDuelNumber) {
            1 => 1.0,
            2 => 0.5,
            3 => 0.25,
            default => 0.2,
        };
    }

    /**
     * Réduction progressive des variations Elo
     * pour les affrontements répétés.
     */
    private function resolveRatingMultiplier(
        int $pairDuelNumber,
    ): float {
        return match ($pairDuelNumber) {
            1 => 1.0,
            2 => 0.5,
            default => 0.0,
        };
    }

    /**
     * Calcule le nombre de secondes restant
     * avant le prochain jour UTC.
     */
    private function secondsUntilNextUtcDay(
        \DateTimeImmutable $now,
    ): int {
        $nextDay = $now
            ->setTime(
                0,
                0,
                0,
            )
            ->modify('+1 day');

        return max(
            1,
            $nextDay->getTimestamp()
            - $now->getTimestamp(),
        );
    }
}
