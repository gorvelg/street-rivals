<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Exception\DuelLimitException;
use App\Model\DuelPolicyResult;
use App\Repository\DuelRepository;

final class DuelPolicyService
{
    public const MAX_DAILY_DUELS = 20;
    public const MAX_DAILY_PAIR_DUELS = 3;
    public const PAIR_COOLDOWN_SECONDS = 600;

    public function __construct(
        private readonly DuelRepository $duelRepository,
    ) {
    }

    public function assertCanStart(
        Car $attacker,
        Car $defender,
        ?\DateTimeImmutable $now = null,
    ): DuelPolicyResult {
        $now ??= new \DateTimeImmutable(
            'now',
            new \DateTimeZone('UTC')
        );

        $nowUtc = $now->setTimezone(
            new \DateTimeZone('UTC')
        );

        $dayStart = $nowUtc->setTime(0, 0);

        $dailyDuelCount = $this->duelRepository
            ->countInitiatedByCarSince(
                attacker: $attacker,
                since: $dayStart,
            );

        if ($dailyDuelCount >= self::MAX_DAILY_DUELS) {
            throw new DuelLimitException(
                message: sprintf(
                    'Cette voiture a déjà lancé %d duels aujourd’hui.',
                    self::MAX_DAILY_DUELS
                ),
                retryAfterSeconds:
                $this->secondsUntilNextUtcDay($nowUtc),
            );
        }

        $pairDuelCount = $this->duelRepository
            ->countBetweenCarsSince(
                firstCar: $attacker,
                secondCar: $defender,
                since: $dayStart,
            );

        if ($pairDuelCount >= self::MAX_DAILY_PAIR_DUELS) {
            throw new DuelLimitException(
                message: sprintf(
                    'Ces deux voitures se sont déjà affrontées %d fois aujourd’hui.',
                    self::MAX_DAILY_PAIR_DUELS
                ),
                retryAfterSeconds:
                $this->secondsUntilNextUtcDay($nowUtc),
            );
        }

        $latestDuel = $this->duelRepository
            ->findLatestBetweenCars(
                firstCar: $attacker,
                secondCar: $defender,
            );

        if ($latestDuel !== null) {
            $nextAvailableAt = $latestDuel
                ->getCreatedAt()
                ->modify(sprintf(
                    '+%d seconds',
                    self::PAIR_COOLDOWN_SECONDS
                ));

            if ($nowUtc < $nextAvailableAt) {
                $retryAfterSeconds = max(
                    1,
                    $nextAvailableAt->getTimestamp()
                    - $nowUtc->getTimestamp()
                );

                throw new DuelLimitException(
                    message: sprintf(
                        'Ces voitures doivent attendre encore %d seconde(s) avant de s’affronter à nouveau.',
                        $retryAfterSeconds
                    ),
                    retryAfterSeconds: $retryAfterSeconds,
                );
            }
        }

        $pairDuelNumber = $pairDuelCount + 1;

        return new DuelPolicyResult(
            dailyDuelNumber: $dailyDuelCount + 1,
            pairDuelNumber: $pairDuelNumber,
            rewardMultiplier: $this->resolveRewardMultiplier(
                $pairDuelNumber
            ),
            ratingMultiplier: $this->resolveRatingMultiplier(
                $pairDuelNumber
            ),
        );
    }

    private function resolveRewardMultiplier(
        int $pairDuelNumber
    ): float {
        return match ($pairDuelNumber) {
            1 => 1.0,
            2 => 0.5,
            3 => 0.25,
            default => 0.0,
        };
    }

    private function resolveRatingMultiplier(
        int $pairDuelNumber
    ): float {
        return match ($pairDuelNumber) {
            1 => 1.0,
            2 => 0.5,
            default => 0.0,
        };
    }

    private function secondsUntilNextUtcDay(
        \DateTimeImmutable $now
    ): int {
        $nextDay = $now
            ->setTime(0, 0)
            ->modify('+1 day');

        return max(
            1,
            $nextDay->getTimestamp() - $now->getTimestamp()
        );
    }
}
