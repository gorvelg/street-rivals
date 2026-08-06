<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Card;
use App\Entity\CarCard;
use App\Repository\CardRepository;
use App\Repository\CarCardRepository;

final class AdminCardDetailService
{
    public function __construct(
        private readonly CardRepository $cardRepository,
        private readonly CarCardRepository $carCardRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetail(
        Card $card,
        ?string $search,
        ?int $tier,
        bool $equippedOnly,
        int $page,
        int $itemsPerPage,
        int $totalItems,
        int $totalPages,
    ): array {
        $stats = $this->cardRepository
            ->findAdminStats($card);

        $carCards = $this->carCardRepository
            ->findAdminPageForCard(
                card: $card,
                search: $search,
                tier: $tier,
                equippedOnly: $equippedOnly,
                page: $page,
                itemsPerPage: $itemsPerPage,
            );

        return [
            'card' => [
                'id' => $card->getId(),
                'code' => $card->getCode(),
                'name' => $card->getName(),
                'type' => $card->getType(),
                'rarity' => $card->getRarity(),

                'effectConfig' =>
                    $card->getEffectConfig() ?? [],

                'carCount' =>
                    $stats['carCount'],

                'tier1Count' =>
                    $stats['tier1Count'],

                'tier2Count' =>
                    $stats['tier2Count'],

                'tier3Count' =>
                    $stats['tier3Count'],

                'equippedCount' =>
                    $stats['equippedCount'],

                'averageAcquiredLevel' =>
                    $stats['averageAcquiredLevel'],
            ],

            'holders' => [
                'members' => array_map(
                    static fn (
                        CarCard $carCard
                    ): array => self::serializeHolder(
                        $carCard
                    ),
                    $carCards
                ),

                'pagination' => [
                    'page' => $page,
                    'itemsPerPage' => $itemsPerPage,
                    'totalItems' => $totalItems,
                    'totalPages' => $totalPages,
                ],

                'filters' => [
                    'search' => $search,
                    'tier' => $tier,
                    'equippedOnly' => $equippedOnly,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function serializeHolder(
        CarCard $carCard,
    ): array {
        $car = $carCard->getCar();
        $owner = $car->getUser();

        return [
            'id' => $carCard->getId(),

            'tier' => $carCard->getTier(),

            'equipped' =>
                $carCard->isEquipped(),

            'acquiredLevel' =>
                $carCard->getAcquiredLevel(),

            'car' => [
                'id' => $car->getId(),
                'pilotName' => $car->getPilotName(),
                'color' => $car->getColor(),

                'level' => $car->getLevel(),
                'rating' => $car->getRating(),

                'wins' => $car->getWins(),
                'losses' => $car->getLosses(),
            ],

            'owner' => [
                'id' => $owner?->getId(),
                'email' => $owner?->getEmail(),

                'isActive' =>
                    $owner?->isActive() ?? false,
            ],
        ];
    }
}
