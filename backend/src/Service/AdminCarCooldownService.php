<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Entity\User;
use App\Enum\GameEventType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

final class AdminCarCooldownService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameEventTracker $eventTracker,
        private readonly DuelPolicyService $duelPolicyService,
    ) {
    }

    /**
     * @return array{
     *     carId: int|null,
     *     updatedDuelCount: int,
     *     cooldownSeconds: int,
     *     resetAt: string
     * }
     */
    public function reset(
        Car $car,
        User $administrator,
    ): array {
        $timezone = new \DateTimeZone('UTC');

        $now = new \DateTimeImmutable(
            'now',
            $timezone
        );

        $cooldownSeconds =
            $this->duelPolicyService
                ->getPairCooldownSeconds();

        if ($cooldownSeconds <= 0) {
            return [
                'carId' => $car->getId(),
                'updatedDuelCount' => 0,
                'cooldownSeconds' => 0,
                'resetAt' => $now->format(
                    \DateTimeInterface::ATOM,
                ),
            ];
        }

        $threshold = $now->modify(
            sprintf(
                '-%d seconds',
                $cooldownSeconds
            )
        );

        /*
         * Les duels concernés sont replacés juste avant
         * la limite du cooldown.
         */
        $resetDate = $threshold->modify('-1 second');

        return $this->entityManager
            ->wrapInTransaction(
                function (
                    EntityManagerInterface $entityManager
                ) use (
                    $car,
                    $administrator,
                    $now,
                    $threshold,
                    $resetDate,
                    $cooldownSeconds
                ): array {
                    $updatedDuelCount = $entityManager
                        ->getConnection()
                        ->executeStatement(
                            <<<'SQL'
UPDATE duel
SET created_at = :resetDate
WHERE (
    attacker_car_id = :carId
    OR defender_car_id = :carId
)
AND created_at > :threshold
SQL,
                            [
                                'carId' => $car->getId(),
                                'resetDate' => $resetDate,
                                'threshold' => $threshold,
                            ],
                            [
                                'carId' =>
                                    ParameterType::INTEGER,

                                'resetDate' =>
                                    Types::DATETIME_IMMUTABLE,

                                'threshold' =>
                                    Types::DATETIME_IMMUTABLE,
                            ]
                        );

                    /*
                     * L’événement est rattaché au propriétaire
                     * et à la voiture concernée.
                     */
                    $this->eventTracker->track(
                        type:
                        GameEventType::
                        ADMIN_COOLDOWN_RESET,

                        user: $car->getUser(),
                        car: $car,

                        payload: [
                            'administratorId' =>
                                $administrator->getId(),

                            'administratorEmail' =>
                                $administrator->getEmail(),

                            'updatedDuelCount' =>
                                $updatedDuelCount,

                            'cooldownSeconds' =>
                                $cooldownSeconds,

                            'resetAt' =>
                                $now->format(
                                    \DateTimeInterface::ATOM
                                ),
                        ],
                    );

                    return [
                        'carId' => $car->getId(),

                        'updatedDuelCount' =>
                            $updatedDuelCount,

                        'cooldownSeconds' =>
                            $cooldownSeconds,

                        'resetAt' =>
                            $now->format(
                                \DateTimeInterface::ATOM
                            ),
                    ];
                }
            );
    }
}
