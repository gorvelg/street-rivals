<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\GameEventType;
use App\Repository\CarRepository;
use App\Repository\DuelRepository;
use App\Repository\GameEventRepository;
use App\Repository\UserRepository;

final class AdminDashboardService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CarRepository $carRepository,
        private readonly DuelRepository $duelRepository,
        private readonly GameEventRepository $gameEventRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboard(): array
    {
        $today = new \DateTimeImmutable(
            'today',
            new \DateTimeZone('UTC')
        );

        return [
            'generatedAt' => (
            new \DateTimeImmutable(
                'now',
                new \DateTimeZone('UTC')
            )
            )->format(\DateTimeInterface::ATOM),

            'timezone' => 'UTC',

            'users' => [
                'total' =>
                    $this->userRepository->count([]),

                'registeredToday' =>
                    $this->gameEventRepository
                        ->countByTypeSince(
                            GameEventType::USER_REGISTERED,
                            $today
                        ),

                'activeToday' =>
                    $this->gameEventRepository
                        ->countDistinctUsersByTypeSince(
                            GameEventType::LOGIN_SUCCEEDED,
                            $today
                        ),
            ],

            'cars' => [
                'total' =>
                    $this->carRepository->count([]),

                'createdToday' =>
                    $this->gameEventRepository
                        ->countByTypeSince(
                            GameEventType::CAR_CREATED,
                            $today
                        ),
            ],

            'duels' => [
                'total' =>
                    $this->duelRepository->count([]),

                'today' =>
                    $this->gameEventRepository
                        ->countByTypeSince(
                            GameEventType::DUEL_COMPLETED,
                            $today
                        ),
            ],

            'progression' => [
                'levelUpsToday' =>
                    $this->gameEventRepository
                        ->countByTypeSince(
                            GameEventType::LEVEL_UP,
                            $today
                        ),

                'cardsSelectedToday' =>
                    $this->gameEventRepository
                        ->countByTypeSince(
                            GameEventType::CARD_SELECTED,
                            $today
                        ),

                'cardsUpgradedToday' =>
                    $this->gameEventRepository
                        ->countByTypeSince(
                            GameEventType::CARD_UPGRADED,
                            $today
                        ),
            ],
        ];
    }
}
