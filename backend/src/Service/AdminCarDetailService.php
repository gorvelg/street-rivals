<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Entity\CarCard;
use App\Entity\Card;
use App\Entity\CardChoice;
use App\Entity\Duel;
use App\Entity\GameEvent;
use App\Repository\CarCardRepository;
use App\Repository\CardChoiceRepository;
use App\Repository\DuelRepository;
use App\Repository\GameEventRepository;

final class AdminCarDetailService
{
    public function __construct(
        private readonly CarCardRepository $carCardRepository,
        private readonly CardChoiceRepository $cardChoiceRepository,
        private readonly DuelRepository $duelRepository,
        private readonly GameEventRepository $gameEventRepository,
        private readonly CarStatsCalculator $carStatsCalculator,
        private readonly DuelPolicyService $duelPolicyService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetail(
        Car $car,
    ): array {
        /** @var list<CarCard> $carCards */
        $carCards = $this->carCardRepository
            ->findForAdminCar($car);

        $pendingChoice = $this->cardChoiceRepository
            ->findPendingForCar($car);

        $recentDuels = $this->duelRepository
            ->findRecentForCar(
                car: $car,
                limit: 10,
            );

        $recentEvents = $this->gameEventRepository
            ->findRecentForCar(
                car: $car,
                limit: 20,
            );

        $calculatedStats = $this->carStatsCalculator
            ->calculate($car);

        $cooldown = $this->buildCooldownStatus(
            $car,
        );

        return [
            'car' => [
                'id' => $car->getId(),
                'pilotName' => $car->getPilotName(),
                'color' => $car->getColor(),

                'level' => $car->getLevel(),
                'xp' => $car->getXp(),
                'money' => $car->getMoney(),

                'rating' => $car->getRating(),
                'wins' => $car->getWins(),
                'losses' => $car->getLosses(),
            ],

            'owner' => [
                'id' => $car->getUser()?->getId(),
                'email' => $car->getUser()?->getEmail(),

                'isActive' =>
                    $car->getUser()?->isActive()
                    ?? false,
            ],

            'baseStats' => [
                'speed' => $car->getSpeed(),

                'acceleration' =>
                    $car->getAcceleration(),

                'grip' => $car->getGrip(),

                'solidity' =>
                    $car->getSolidity(),
            ],

            'calculatedStats' =>
                $calculatedStats,

            'cards' => array_map(
                fn (CarCard $carCard): array =>
                $this->serializeCarCard(
                    $carCard,
                ),
                $carCards,
            ),

            'pendingCardChoice' =>
                $pendingChoice instanceof CardChoice
                    ? $this->serializeCardChoice(
                    $pendingChoice,
                )
                    : null,

            'recentDuels' => array_map(
                fn (Duel $duel): array =>
                $this->serializeDuel(
                    duel: $duel,
                    car: $car,
                ),
                $recentDuels,
            ),

            'recentEvents' => array_map(
                fn (GameEvent $event): array =>
                $this->serializeEvent(
                    $event,
                ),
                $recentEvents,
            ),

            'cooldown' => $cooldown,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCarCard(
        CarCard $carCard,
    ): array {
        $card = $carCard->getCard();

        return [
            'id' => $carCard->getId(),
            'tier' => $carCard->getTier(),

            'equipped' =>
                $carCard->isEquipped(),

            'acquiredLevel' =>
                $carCard->getAcquiredLevel(),

            'card' => [
                'id' => $card?->getId(),
                'code' => $card?->getCode(),
                'name' => $card?->getName(),
                'type' => $card?->getType(),
                'rarity' => $card?->getRarity(),

                'effectConfig' =>
                    $card?->getEffectConfig()
                    ?? [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCardChoice(
        CardChoice $choice,
    ): array {
        return [
            'id' => $choice->getId(),
            'level' => $choice->getLevel(),

            'firstCard' => $this->serializeCard(
                $choice->getFirstCard(),
            ),

            'secondCard' => $this->serializeCard(
                $choice->getSecondCard(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeCard(
        ?Card $card,
    ): ?array {
        if (!$card instanceof Card) {
            return null;
        }

        return [
            'id' => $card->getId(),
            'code' => $card->getCode(),
            'name' => $card->getName(),
            'type' => $card->getType(),
            'rarity' => $card->getRarity(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDuel(
        Duel $duel,
        Car $car,
    ): array {
        $attacker = $duel->getAttackerCar();
        $defender = $duel->getDefenderCar();
        $winner = $duel->getWinnerCar();

        $carWasAttacker =
            $attacker->getId()
            === $car->getId();

        $opponent = $carWasAttacker
            ? $defender
            : $attacker;

        return [
            'id' => $duel->getId(),

            'opponent' => [
                'id' => $opponent->getId(),

                'pilotName' =>
                    $opponent->getPilotName(),

                'color' => $opponent->getColor(),
            ],

            'wasAttacker' => $carWasAttacker,

            'winnerCarId' =>
                $winner->getId(),

            'won' =>
                $winner->getId()
                === $car->getId(),

            'finalGap' =>
                $duel->getFinalGap(),

            'createdAt' => $duel
                ->getCreatedAt()
                ->format(
                    \DateTimeInterface::ATOM,
                ),
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

            'type' =>
                $event->getType()->value,

            'duelId' =>
                $event->getDuel()?->getId(),

            'payload' =>
                $event->getPayload(),

            'occurredAt' => $event
                ->getOccurredAt()
                ->format(
                    \DateTimeInterface::ATOM,
                ),
        ];
    }

    /**
     * @return array{
     *     active: bool,
     *     activePairCount: int,
     *     cooldownSeconds: int,
     *     pairs: list<array<string, mixed>>
     * }
     */
    private function buildCooldownStatus(
        Car $car,
    ): array {
        $timezone = new \DateTimeZone('UTC');

        $now = new \DateTimeImmutable(
            'now',
            $timezone,
        );

        /*
         * La valeur vient maintenant des paramètres
         * administrables du gameplay.
         */
        $cooldownSeconds =
            $this->duelPolicyService
                ->getPairCooldownSeconds();

        /*
         * La valeur 0 désactive complètement
         * le cooldown entre les voitures.
         */
        if ($cooldownSeconds <= 0) {
            return [
                'active' => false,
                'activePairCount' => 0,
                'cooldownSeconds' => 0,
                'pairs' => [],
            ];
        }

        $since = $now->modify(
            sprintf(
                '-%d seconds',
                $cooldownSeconds,
            ),
        );

        $recentDuels = $this->duelRepository
            ->findRecentForCarSince(
                car: $car,
                since: $since,
            );

        /*
         * Les duels sont triés du plus récent
         * au plus ancien.
         *
         * On conserve uniquement le duel le plus
         * récent pour chaque adversaire.
         *
         * @var array<int, array<string, mixed>>
         */
        $pairsByOpponent = [];

        foreach ($recentDuels as $duel) {
            $attacker =
                $duel->getAttackerCar();

            $defender =
                $duel->getDefenderCar();

            $opponent =
                $attacker->getId()
                === $car->getId()
                    ? $defender
                    : $attacker;

            $opponentId =
                $opponent->getId();

            if ($opponentId === null) {
                continue;
            }

            if (
                isset(
                    $pairsByOpponent[
                    $opponentId
                    ],
                )
            ) {
                continue;
            }

            $expiresAt = $duel
                ->getCreatedAt()
                ->modify(
                    sprintf(
                        '+%d seconds',
                        $cooldownSeconds,
                    ),
                );

            $remainingSeconds = max(
                0,
                $expiresAt->getTimestamp()
                - $now->getTimestamp(),
            );

            if ($remainingSeconds <= 0) {
                continue;
            }

            $pairsByOpponent[$opponentId] = [
                'opponent' => [
                    'id' => $opponentId,

                    'pilotName' =>
                        $opponent
                            ->getPilotName(),

                    'color' =>
                        $opponent->getColor(),
                ],

                'lastDuelId' =>
                    $duel->getId(),

                'lastDuelAt' => $duel
                    ->getCreatedAt()
                    ->format(
                        \DateTimeInterface::ATOM,
                    ),

                'expiresAt' =>
                    $expiresAt->format(
                        \DateTimeInterface::ATOM,
                    ),

                'remainingSeconds' =>
                    $remainingSeconds,
            ];
        }

        $pairs = array_values(
            $pairsByOpponent,
        );

        return [
            'active' => $pairs !== [],

            'activePairCount' =>
                count($pairs),

            'cooldownSeconds' =>
                $cooldownSeconds,

            'pairs' => $pairs,
        ];
    }
}
