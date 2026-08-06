<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CarStatsOutput;
use App\Entity\Car;
use App\Model\DuelSimulationResult;

final class DuelSimulator
{
    public const ENGINE_VERSION = '1.0.0';

    private const MAXIMUM_GAP_CHANGE = 5;

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

        $attackerStats = $this->statsCalculator->calculate($attacker);
        $defenderStats = $this->statsCalculator->calculate($defender);

        $gap = 0;
        $events = [];

        foreach (self::EVENTS as $index => $eventDefinition) {
            $attackerBaseScore = $this->calculateBaseScore(
                $attackerStats->effective,
                $eventDefinition['weights']
            );

            $defenderBaseScore = $this->calculateBaseScore(
                $defenderStats->effective,
                $eventDefinition['weights']
            );

            $attackerRandomModifier = $this->random->integer(
                seed: $seed,
                scope: sprintf('event:%d:attacker', $index),
                minimum: -2,
                maximum: 2,
            );

            $defenderRandomModifier = $this->random->integer(
                seed: $seed,
                scope: sprintf('event:%d:defender', $index),
                minimum: -2,
                maximum: 2,
            );

            $attackerScore =
                $attackerBaseScore + $attackerRandomModifier;

            $defenderScore =
                $defenderBaseScore + $defenderRandomModifier;

            $rawDifference = $attackerScore - $defenderScore;

            /*
             * On limite la variation à cinq points par événement
             * pour éviter qu’un seul événement décide de toute la course.
             */
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
                'type' => $eventDefinition['type'],
                'label' => $eventDefinition['label'],
                'attacker' => [
                    'baseScore' => $attackerBaseScore,
                    'randomModifier' => $attackerRandomModifier,
                    'score' => $attackerScore,
                ],
                'defender' => [
                    'baseScore' => $defenderBaseScore,
                    'randomModifier' => $defenderRandomModifier,
                    'score' => $defenderScore,
                ],
                'rawDifference' => $rawDifference,
                'gapChange' => $gapChange,
                'gapAfter' => $gap,
                'leader' => $this->resolveLeader($gap),
            ];
        }

        /*
         * Il faut toujours un gagnant.
         * En cas d’égalité, on ajoute un photo-finish reproductible.
         */
        if ($gap === 0) {
            $photoFinishWinner = $this->random->integer(
                seed: $seed,
                scope: 'photo_finish',
                minimum: 0,
                maximum: 1,
            );

            $gap = $photoFinishWinner === 1 ? 1 : -1;

            $events[] = [
                'index' => count($events) + 1,
                'type' => 'photo_finish',
                'label' => 'Photo-finish',
                'gapChange' => $gap,
                'gapAfter' => $gap,
                'leader' => $this->resolveLeader($gap),
            ];
        }

        $winnerCar = $gap > 0
            ? $attacker
            : $defender;

        $attackerSnapshot = $this->createSnapshot(
            $attacker,
            $attackerStats
        );

        $defenderSnapshot = $this->createSnapshot(
            $defender,
            $defenderStats
        );

        $winnerCarId = $winnerCar->getId();

        if ($winnerCarId === null) {
            throw new \LogicException(
                'La voiture gagnante doit être enregistrée.'
            );
        }

        $replayData = [
            'engineVersion' => self::ENGINE_VERSION,
            'randomSeed' => $seed,
            'events' => $events,
            'finalGap' => $gap,
            'winnerCarId' => $winnerCarId,
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
     * @param array<string, int> $effectiveStats
     * @param array<string, float> $weights
     */
    private function calculateBaseScore(
        array $effectiveStats,
        array $weights,
    ): int {
        $score = 0.0;

        foreach ($weights as $stat => $weight) {
            if (!array_key_exists($stat, $effectiveStats)) {
                throw new \LogicException(sprintf(
                    'La statistique "%s" est absente.',
                    $stat
                ));
            }

            $score += $effectiveStats[$stat] * $weight;
        }

        return (int) round($score);
    }

    private function resolveLeader(int $gap): string
    {
        return match (true) {
            $gap > 0 => 'attacker',
            $gap < 0 => 'defender',
            default => 'tie',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function createSnapshot(
        Car $car,
        CarStatsOutput $stats,
    ): array {
        return [
            'carId' => $stats->carId,
            'pilotName' => $stats->pilotName,
            'color' => $car->getColor(),
            'level' => $stats->level,
            'base' => $stats->base,
            'bonuses' => $stats->bonuses,
            'effective' => $stats->effective,
            'appliedCards' => $stats->appliedCards,
        ];
    }
}
