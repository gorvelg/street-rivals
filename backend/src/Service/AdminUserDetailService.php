<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Entity\Duel;
use App\Entity\GameEvent;
use App\Entity\User;
use App\Enum\GameEventType;
use App\Repository\CarRepository;
use App\Repository\DuelRepository;
use App\Repository\GameEventRepository;

final class AdminUserDetailService
{
    public function __construct(
        private readonly CarRepository $carRepository,
        private readonly DuelRepository $duelRepository,
        private readonly GameEventRepository $gameEventRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetail(
        User $user,
    ): array {
        /** @var list<Car> $cars */
        $cars = $this->carRepository->findBy(
            [
                'user' => $user,
            ],
            [
                'id' => 'ASC',
            ]
        );

        $recentEvents = $this->gameEventRepository
            ->findRecentForUser(
                user: $user,
                limit: 20,
            );

        $recentDuels = $this->duelRepository
            ->findRecentForUser(
                user: $user,
                limit: 10,
            );

        $lastLoginEvent = $this->gameEventRepository
            ->findLastForUserAndType(
                user: $user,
                type: GameEventType::LOGIN_SUCCEEDED,
            );

        $wins = 0;
        $losses = 0;

        foreach ($cars as $car) {
            $wins += $car->getWins();
            $losses += $car->getLosses();
        }

        return [
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'isActive' => $user->isActive(),
            ],

            'statistics' => [
                'carCount' => count($cars),
                'duelCount' =>
                    $this->duelRepository
                        ->countForUser($user),

                'wins' => $wins,
                'losses' => $losses,

                'lastLoginAt' =>
                    $lastLoginEvent?->getOccurredAt()
                        ->format(
                            \DateTimeInterface::ATOM
                        ),
            ],

            'cars' => array_map(
                fn (Car $car): array =>
                $this->serializeCar($car),
                $cars
            ),

            'recentEvents' => array_map(
                fn (GameEvent $event): array =>
                $this->serializeEvent($event),
                $recentEvents
            ),

            'recentDuels' => array_map(
                fn (Duel $duel): array =>
                $this->serializeDuel(
                    duel: $duel,
                    user: $user,
                ),
                $recentDuels
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCar(
        Car $car,
    ): array {
        return [
            'id' => $car->getId(),
            'pilotName' => $car->getPilotName(),
            'color' => $car->getColor(),

            'level' => $car->getLevel(),
            'xp' => $car->getXp(),
            'money' => $car->getMoney(),

            'rating' => $car->getRating(),
            'wins' => $car->getWins(),
            'losses' => $car->getLosses(),

            'stats' => [
                'speed' => $car->getSpeed(),
                'acceleration' =>
                    $car->getAcceleration(),
                'grip' => $car->getGrip(),
                'solidity' => $car->getSolidity(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEvent(
        GameEvent $event,
    ): array {
        return [
            'id' => $event->getId(),
            'type' => $event->getType()->value,
            'carId' => $event->getCar()?->getId(),
            'duelId' => $event->getDuel()?->getId(),
            'payload' => $event->getPayload(),
            'occurredAt' =>
                $event->getOccurredAt()->format(
                    \DateTimeInterface::ATOM
                ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDuel(
        Duel $duel,
        User $user,
    ): array {
        $attacker = $duel->getAttackerCar();
        $defender = $duel->getDefenderCar();
        $winner = $duel->getWinnerCar();

        $userWasAttacker =
            $attacker->getUser()?->getId()
            === $user->getId();

        $userCar = $userWasAttacker
            ? $attacker
            : $defender;

        $opponentCar = $userWasAttacker
            ? $defender
            : $attacker;

        return [
            'id' => $duel->getId(),

            'userCar' => [
                'id' => $userCar->getId(),
                'pilotName' =>
                    $userCar->getPilotName(),
                'color' => $userCar->getColor(),
            ],

            'opponentCar' => [
                'id' => $opponentCar->getId(),
                'pilotName' =>
                    $opponentCar->getPilotName(),
                'color' =>
                    $opponentCar->getColor(),
            ],

            'winnerCarId' => $winner->getId(),

            'won' =>
                $winner->getId()
                === $userCar->getId(),

            'finalGap' => $duel->getFinalGap(),

            'createdAt' =>
                $duel->getCreatedAt()->format(
                    \DateTimeInterface::ATOM
                ),
        ];
    }
}
