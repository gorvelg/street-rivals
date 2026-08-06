<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\MatchmakingOpponentOutput;
use App\Entity\Car;
use App\Repository\CarRepository;

final class MatchmakingService
{
    private const LEVEL_RANGE = 2;
    private const PRESELECTION_LIMIT = 50;
    private const MAXIMUM_OPPONENTS = 10;

    /*
     * Une différence inférieure à 10 % est considérée
     * comme un affrontement équilibré.
     */
    private const BALANCED_MARGIN_PERCENT = 10.0;

    public function __construct(
        private readonly CarRepository $carRepository,
        private readonly CarStatsCalculator $statsCalculator,
    ) {
    }

    /**
     * @return list<MatchmakingOpponentOutput>
     */
    public function findOpponents(Car $referenceCar): array
    {
        $referenceStats = $this->statsCalculator
            ->calculate($referenceCar);

        $referencePower = $this->calculatePowerScore(
            $referenceStats->effective
        );

        $candidates = $this->carRepository
            ->findMatchmakingCandidates(
                referenceCar: $referenceCar,
                levelRange: self::LEVEL_RANGE,
                limit: self::PRESELECTION_LIMIT,
            );

        $rankedOpponents = [];

        foreach ($candidates as $candidate) {
            $candidateStats = $this->statsCalculator
                ->calculate($candidate);

            $candidatePower = $this->calculatePowerScore(
                $candidateStats->effective
            );

            $powerDifferencePercent = round(
                (
                    ($candidatePower - $referencePower)
                    / max(1, $referencePower)
                ) * 100,
                2
            );

            $levelDifference =
                $candidate->getLevel()
                - $referenceCar->getLevel();

            $candidateId = $candidate->getId();

            if ($candidateId === null) {
                continue;
            }

            $output = new MatchmakingOpponentOutput(
                carId: $candidateId,
                pilotName: $candidate->getPilotName() ?? '',
                color: $candidate->getColor() ?? '#000000',
                level: $candidate->getLevel(),
                levelDifference: $levelDifference,
                effectiveStats: $candidateStats->effective,
                powerScore: $candidatePower,
                powerDifferencePercent: $powerDifferencePercent,
                difficulty: $this->resolveDifficulty(
                    $powerDifferencePercent
                ),
                potentialRewards: [
                    'victory' => [
                        'xp' => DuelRewardCalculator::WINNER_XP,
                        'money' => DuelRewardCalculator::WINNER_MONEY,
                    ],
                    'defeat' => [
                        'xp' => DuelRewardCalculator::LOSER_XP,
                        'money' => DuelRewardCalculator::LOSER_MONEY,
                    ],
                ],
            );

            $rankedOpponents[] = [
                'output' => $output,
                'absolutePowerDifference' => abs(
                    $powerDifferencePercent
                ),
                'absoluteLevelDifference' => abs(
                    $levelDifference
                ),
                'carId' => $candidateId,
            ];
        }

        /*
         * Priorités :
         *
         * 1. puissance la plus proche ;
         * 2. niveau le plus proche ;
         * 3. identifiant, pour avoir un ordre stable.
         */
        usort(
            $rankedOpponents,
            static function (array $first, array $second): int {
                $powerComparison =
                    $first['absolutePowerDifference']
                    <=> $second['absolutePowerDifference'];

                if ($powerComparison !== 0) {
                    return $powerComparison;
                }

                $levelComparison =
                    $first['absoluteLevelDifference']
                    <=> $second['absoluteLevelDifference'];

                if ($levelComparison !== 0) {
                    return $levelComparison;
                }

                return $first['carId'] <=> $second['carId'];
            }
        );

        $rankedOpponents = array_slice(
            $rankedOpponents,
            0,
            self::MAXIMUM_OPPONENTS
        );

        return array_map(
            static fn (array $entry): MatchmakingOpponentOutput =>
            $entry['output'],
            $rankedOpponents
        );
    }

    /**
     * @param array{
     *     speed: int,
     *     acceleration: int,
     *     grip: int,
     *     solidity: int
     * } $effectiveStats
     */
    private function calculatePowerScore(
        array $effectiveStats
    ): int {
        return
            $effectiveStats['speed']
            + $effectiveStats['acceleration']
            + $effectiveStats['grip']
            + $effectiveStats['solidity'];
    }

    private function resolveDifficulty(
        float $powerDifferencePercent
    ): string {
        if (
            $powerDifferencePercent
            <= -self::BALANCED_MARGIN_PERCENT
        ) {
            return 'EASY';
        }

        if (
            $powerDifferencePercent
            >= self::BALANCED_MARGIN_PERCENT
        ) {
            return 'HARD';
        }

        return 'BALANCED';
    }
}
