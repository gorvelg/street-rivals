<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CarStatsOutput;
use App\Entity\Car;
use App\Entity\CarCard;
use App\Enum\NarrativeContext;
use App\Model\DuelSimulationResult;
use App\Repository\CarCardRepository;

final class DuelSimulator
{
    public const ENGINE_VERSION = '1.3.0';

    private const MAXIMUM_GAP_CHANGE = 5;

    private const AVAILABLE_STATS = [
        'speed',
        'acceleration',
        'grip',
        'solidity',
    ];

    /**
     * @var list<array{
     *     type: string,
     *     label: string,
     *     weights: array<string, float>
     * }>
     */
    private const EVENTS = [
        [
            'type' => 'start',
            'label' => 'Départ',
            'weights' => [
                'acceleration' => 1.0,
            ],
        ],
        [
            'type' => 'straight',
            'label' => 'Ligne droite',
            'weights' => [
                'speed' => 1.0,
            ],
        ],
        [
            'type' => 'turn',
            'label' => 'Virage',
            'weights' => [
                'grip' => 1.0,
            ],
        ],
        [
            'type' => 'chicane',
            'label' => 'Chicane',
            'weights' => [
                'grip' => 0.7,
                'solidity' => 0.3,
            ],
        ],
        [
            'type' => 'final_sprint',
            'label' => 'Sprint final',
            'weights' => [
                'speed' => 0.6,
                'acceleration' => 0.4,
            ],
        ],
    ];

    public function __construct(
        private readonly CarStatsCalculator $statsCalculator,
        private readonly DeterministicRandom $random,
        private readonly CarCardRepository $carCardRepository,
        private readonly CardEffectResolver $cardEffectResolver,
        private readonly CardNarrativeService $cardNarrativeService,
    ) {
    }

    public function simulate(
        Car $attacker,
        Car $defender,
        ?string $seed = null,
    ): DuelSimulationResult {
        if ($attacker->getId() === $defender->getId()) {
            throw new \DomainException(
                'Une voiture ne peut pas s’affronter elle-même.'
            );
        }

        $seed ??= bin2hex(random_bytes(16));

        $attackerStats = $this->statsCalculator->calculate(
            $attacker
        );

        $defenderStats = $this->statsCalculator->calculate(
            $defender
        );

        $attackerActiveCards =
            $this->findActiveCards($attacker);

        $defenderActiveCards =
            $this->findActiveCards($defender);

        /*
         * Nombre d’activations de chaque CarCard
         * pendant ce duel.
         */
        $attackerActivationCounts = [];
        $defenderActivationCounts = [];

        $gap = 0;
        $events = [];

        foreach (
            self::EVENTS
            as $index => $eventDefinition
        ) {
            /*
             * Scores avant application
             * des cartes actives.
             */
            $attackerPermanentScore =
                $this->calculateBaseScore(
                    $attackerStats->effective,
                    $eventDefinition['weights']
                );

            $defenderPermanentScore =
                $this->calculateBaseScore(
                    $defenderStats->effective,
                    $eventDefinition['weights']
                );

            /*
             * Les statistiques événementielles
             * sont temporaires.
             */
            $attackerEventStats =
                $attackerStats->effective;

            $defenderEventStats =
                $defenderStats->effective;

            $attackerTriggeredCards =
                $this->applyActiveCards(
                    activeCards:
                    $attackerActiveCards,

                    eventType:
                    $eventDefinition['type'],

                    eventStats:
                    $attackerEventStats,

                    activationCounts:
                    $attackerActivationCounts,

                    pilotName:
                    $attackerStats->pilotName,

                    opponentName:
                    $defenderStats->pilotName,

                    duelSeed:
                    $seed,

                    eventIndex:
                    $index,

                    side:
                    'attacker',
                );

            $defenderTriggeredCards =
                $this->applyActiveCards(
                    activeCards:
                    $defenderActiveCards,

                    eventType:
                    $eventDefinition['type'],

                    eventStats:
                    $defenderEventStats,

                    activationCounts:
                    $defenderActivationCounts,

                    pilotName:
                    $defenderStats->pilotName,

                    opponentName:
                    $attackerStats->pilotName,

                    duelSeed:
                    $seed,

                    eventIndex:
                    $index,

                    side:
                    'defender',
                );

            /*
             * Scores après application
             * des cartes actives.
             */
            $attackerBaseScore =
                $this->calculateBaseScore(
                    $attackerEventStats,
                    $eventDefinition['weights']
                );

            $defenderBaseScore =
                $this->calculateBaseScore(
                    $defenderEventStats,
                    $eventDefinition['weights']
                );

            $attackerActiveBonus =
                $attackerBaseScore
                - $attackerPermanentScore;

            $defenderActiveBonus =
                $defenderBaseScore
                - $defenderPermanentScore;

            $attackerRandomModifier =
                $this->random->integer(
                    seed: $seed,
                    scope: sprintf(
                        'event:%d:attacker',
                        $index
                    ),
                    minimum: -2,
                    maximum: 2,
                );

            $defenderRandomModifier =
                $this->random->integer(
                    seed: $seed,
                    scope: sprintf(
                        'event:%d:defender',
                        $index
                    ),
                    minimum: -2,
                    maximum: 2,
                );

            $attackerScore =
                $attackerBaseScore
                + $attackerRandomModifier;

            $defenderScore =
                $defenderBaseScore
                + $defenderRandomModifier;

            $rawDifference =
                $attackerScore
                - $defenderScore;

            $gapChange = max(
                -self::MAXIMUM_GAP_CHANGE,
                min(
                    self::MAXIMUM_GAP_CHANGE,
                    $rawDifference
                )
            );

            $gap += $gapChange;

            $events[] = [
                'index' => $index + 1,
                'type' =>
                    $eventDefinition['type'],

                'label' =>
                    $eventDefinition['label'],

                'attacker' => [
                    'permanentScore' =>
                        $attackerPermanentScore,

                    'activeCardBonus' =>
                        $attackerActiveBonus,

                    'baseScore' =>
                        $attackerBaseScore,

                    'randomModifier' =>
                        $attackerRandomModifier,

                    'score' =>
                        $attackerScore,

                    'triggeredCards' =>
                        $attackerTriggeredCards,
                ],

                'defender' => [
                    'permanentScore' =>
                        $defenderPermanentScore,

                    'activeCardBonus' =>
                        $defenderActiveBonus,

                    'baseScore' =>
                        $defenderBaseScore,

                    'randomModifier' =>
                        $defenderRandomModifier,

                    'score' =>
                        $defenderScore,

                    'triggeredCards' =>
                        $defenderTriggeredCards,
                ],

                'rawDifference' =>
                    $rawDifference,

                'gapChange' =>
                    $gapChange,

                'gapAfter' =>
                    $gap,

                'leader' =>
                    $this->resolveLeader($gap),
            ];
        }

        /*
         * Il faut toujours un gagnant.
         */
        if ($gap === 0) {
            $photoFinishWinner =
                $this->random->integer(
                    seed: $seed,
                    scope: 'photo_finish',
                    minimum: 0,
                    maximum: 1,
                );

            $gap =
                $photoFinishWinner === 1
                    ? 1
                    : -1;

            $events[] = [
                'index' =>
                    count($events) + 1,

                'type' =>
                    'photo_finish',

                'label' =>
                    'Photo-finish',

                'gapChange' =>
                    $gap,

                'gapAfter' =>
                    $gap,

                'leader' =>
                    $this->resolveLeader($gap),
            ];
        }

        $winnerCar =
            $gap > 0
                ? $attacker
                : $defender;

        $attackerSnapshot =
            $this->createSnapshot(
                car: $attacker,
                stats: $attackerStats,
                activeCards:
                $attackerActiveCards,
            );

        $defenderSnapshot =
            $this->createSnapshot(
                car: $defender,
                stats: $defenderStats,
                activeCards:
                $defenderActiveCards,
            );

        $winnerCarId =
            $winnerCar->getId();

        if ($winnerCarId === null) {
            throw new \LogicException(
                'La voiture gagnante doit être enregistrée.'
            );
        }

        $replayData = [
            'engineVersion' =>
                self::ENGINE_VERSION,

            'randomSeed' =>
                $seed,

            'events' =>
                $events,

            'finalGap' =>
                $gap,

            'winnerCarId' =>
                $winnerCarId,
        ];

        return new DuelSimulationResult(
            winnerCar: $winnerCar,
            finalGap: $gap,
            randomSeed: $seed,
            engineVersion: self::ENGINE_VERSION,
            attackerSnapshot: $attackerSnapshot,
            defenderSnapshot: $defenderSnapshot,
            replayData: $replayData,
        );
    }

    /**
     * @return list<array{
     *     carCard: CarCard,
     *     effect: array<string, mixed>
     * }>
     */
    private function findActiveCards(
        Car $car,
    ): array {
        $activeCards = [];

        foreach (
            $this->carCardRepository
                ->findEquippedByCar($car)
            as $carCard
        ) {
            $card =
                $carCard->getCard();

            if ($card === null) {
                continue;
            }

            if (!$card->isEnabled()) {
                continue;
            }

            if ($card->getType() !== 'ACTIVE') {
                continue;
            }

            $effect =
                $this->cardEffectResolver
                    ->resolve($carCard);

            if (
                ($effect['kind'] ?? null)
                !== 'event_stat_bonus'
            ) {
                continue;
            }

            $this->validateActiveCardEffect(
                carCard: $carCard,
                effect: $effect,
            );

            $activeCards[] = [
                'carCard' =>
                    $carCard,

                'effect' =>
                    $effect,
            ];
        }

        return $activeCards;
    }

    /**
     * @param list<array{
     *     carCard: CarCard,
     *     effect: array<string, mixed>
     * }> $activeCards
     *
     * @param array<string, int> $eventStats
     * @param array<int, int> $activationCounts
     *
     * @return list<array<string, mixed>>
     */
    private function applyActiveCards(
        array $activeCards,
        string $eventType,
        array &$eventStats,
        array &$activationCounts,
        string $pilotName,
        string $opponentName,
        string $duelSeed,
        int $eventIndex,
        string $side,
    ): array {
        $triggeredCards = [];

        $narrativeContext =
            $this->resolveNarrativeContext(
                $eventType
            );

        foreach ($activeCards as $activeCard) {
            $carCard =
                $activeCard['carCard'];

            $effect =
                $activeCard['effect'];

            if (
                ($effect['event'] ?? null)
                !== $eventType
            ) {
                continue;
            }

            $carCardId =
                $carCard->getId();

            if ($carCardId === null) {
                throw new \LogicException(
                    'Une carte active doit être enregistrée.'
                );
            }

            $maxActivations =
                (int) (
                    $effect['maxActivations']
                    ?? 1
                );

            $currentActivations =
                $activationCounts[$carCardId]
                ?? 0;

            if (
                $currentActivations
                >= $maxActivations
            ) {
                continue;
            }

            $stat =
                (string) $effect['stat'];

            $value =
                (int) $effect['value'];

            $eventStats[$stat] +=
                $value;

            $activationNumber =
                $currentActivations + 1;

            $activationCounts[$carCardId] =
                $activationNumber;

            $card =
                $carCard->getCard();

            if ($card === null) {
                throw new \LogicException(
                    'La carte active est introuvable.'
                );
            }

            /*
             * La narration est générée MAINTENANT,
             * pendant la simulation.
             *
             * Les lignes sont ensuite stockées
             * dans replayData avec le reste du duel.
             */
            $narrativeLines =
                $this->cardNarrativeService
                    ->buildCardLines(
                        card: $card,

                        pilotName:
                        $pilotName,

                        opponentName:
                        $opponentName,

                        context:
                        $narrativeContext,

                        value:
                        $value,

                        stat:
                        $stat,

                        seed:
                        sprintf(
                            '%s:event:%d:%s:car-card:%d:activation:%d',
                            $duelSeed,
                            $eventIndex,
                            $side,
                            $carCardId,
                            $activationNumber,
                        ),
                    );

            $triggeredCards[] = [
                'carCardId' =>
                    $carCardId,

                'cardId' =>
                    $card->getId(),

                'code' =>
                    $card->getCode(),

                'name' =>
                    $card->getName(),

                'tier' =>
                    $carCard->getTier(),

                'stat' =>
                    $stat,

                'value' =>
                    $value,

                'activationNumber' =>
                    $activationNumber,

                'maxActivations' =>
                    $maxActivations,

                /*
                 * Nouveau en moteur 1.2.0
                 */
                'narrativeLines' =>
                    $narrativeLines,
            ];
        }

        return $triggeredCards;
    }

    private function resolveNarrativeContext(
        string $eventType,
    ): NarrativeContext {
        return match ($eventType) {
            'start' =>
            NarrativeContext::START,

            'straight' =>
            NarrativeContext::STRAIGHT,

            /*
             * Une chicane est considérée comme
             * un contexte de pilotage en virage.
             */
            'turn',
            'chicane' =>
            NarrativeContext::TURN,

            /*
             * Le sprint final privilégie
             * vitesse + accélération.
             * Pour la narration des capacités,
             * on le rapproche d'une ligne droite.
             */
            'final_sprint' =>
            NarrativeContext::STRAIGHT,

            default =>
            NarrativeContext::ANY,
        };
    }

    /**
     * @param array<string, mixed> $effect
     */
    private function validateActiveCardEffect(
        CarCard $carCard,
        array $effect,
    ): void {
        $cardName =
            $carCard->getCard()?->getName()
            ?? 'Carte inconnue';

        $event =
            $effect['event']
            ?? null;

        $stat =
            $effect['stat']
            ?? null;

        $value =
            $effect['value']
            ?? null;

        $maxActivations =
            $effect['maxActivations']
            ?? 1;

        if (
            !is_string($event)
            || $event === ''
        ) {
            throw new \LogicException(
                sprintf(
                    'L’événement de la carte "%s" est invalide.',
                    $cardName
                )
            );
        }

        if (
            !is_string($stat)
            || !in_array(
                $stat,
                self::AVAILABLE_STATS,
                true
            )
        ) {
            throw new \LogicException(
                sprintf(
                    'La statistique de la carte "%s" est invalide.',
                    $cardName
                )
            );
        }

        if (
            !is_int($value)
            && !is_float($value)
        ) {
            throw new \LogicException(
                sprintf(
                    'La valeur de la carte "%s" est invalide.',
                    $cardName
                )
            );
        }

        if (
            !is_int($maxActivations)
            || $maxActivations < 1
        ) {
            throw new \LogicException(
                sprintf(
                    'Le nombre maximal d’activations de la carte "%s" est invalide.',
                    $cardName
                )
            );
        }
    }

    /**
     * @param array<string, int> $effectiveStats
     * @param array<string, float> $weights
     */
    private function calculateBaseScore(
        array $effectiveStats,
        array $weights,
    ): int {
        $score = 0.0;

        foreach (
            $weights
            as $stat => $weight
        ) {
            if (
                !array_key_exists(
                    $stat,
                    $effectiveStats
                )
            ) {
                throw new \LogicException(
                    sprintf(
                        'La statistique "%s" est absente.',
                        $stat
                    )
                );
            }

            $score +=
                $effectiveStats[$stat]
                * $weight;
        }

        return (int) round($score);
    }

    private function resolveLeader(
        int $gap,
    ): string {
        return match (true) {
            $gap > 0 =>
            'attacker',

            $gap < 0 =>
            'defender',

            default =>
            'tie',
        };
    }

    /**
     * @param list<array{
     *     carCard: CarCard,
     *     effect: array<string, mixed>
     * }> $activeCards
     *
     * @return array<string, mixed>
     */
    private function createSnapshot(
        Car $car,
        CarStatsOutput $stats,
        array $activeCards,
    ): array {
        return [
            'carId' =>
                $stats->carId,

            'pilotName' =>
                $stats->pilotName,

            'color' =>
                $car->getColor(),

            'bodyStyle' =>
                $car
                    ->getBodyStyle()
                    ->value,

            'wheelStyle' =>
                $car
                    ->getWheelStyle()
                    ->value,

            'level' =>
                $stats->level,

            'level' =>
                $stats->level,

            'base' =>
                $stats->base,

            'bonuses' =>
                $stats->bonuses,

            'effective' =>
                $stats->effective,

            'passiveCards' =>
                $stats->appliedCards,

            'activeCards' =>
                array_map(
                    static function (
                        array $activeCard,
                    ): array {
                        /** @var CarCard $carCard */
                        $carCard =
                            $activeCard['carCard'];

                        $card =
                            $carCard->getCard();

                        return [
                            'carCardId' =>
                                $carCard->getId(),

                            'cardId' =>
                                $card?->getId(),

                            'code' =>
                                $card?->getCode(),

                            'name' =>
                                $card?->getName(),

                            'tier' =>
                                $carCard->getTier(),

                            'effect' =>
                                $activeCard['effect'],
                        ];
                    },
                    $activeCards
                ),
        ];
    }
}
