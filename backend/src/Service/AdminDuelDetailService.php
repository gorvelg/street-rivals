<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Entity\Duel;

final class AdminDuelDetailService
{
    /**
     * @return array<string, mixed>
     */
    public function getDetail(Duel $duel): array
    {
        $attacker = $duel->getAttackerCar();
        $defender = $duel->getDefenderCar();
        $winner = $duel->getWinnerCar();

        $replayData = $duel->getReplayData();

        return [
            'duel' => [
                'id' => $duel->getId(),

                'createdAt' => $duel
                    ->getCreatedAt()
                    ->format(\DateTimeInterface::ATOM),

                'finalGap' => $duel->getFinalGap(),
                'randomSeed' => $duel->getRandomSeed(),
                'engineVersion' => $duel->getEngineVersion(),

                'winnerCarId' => $winner->getId(),

                'winnerSide' =>
                    $winner->getId() === $attacker->getId()
                        ? 'attacker'
                        : 'defender',
            ],

            'attacker' => $this->serializeCar($attacker),

            'defender' => $this->serializeCar($defender),

            'rewards' => [
                'attacker' => [
                    'xp' => $duel->getAttackerXpReward(),
                    'money' => $duel->getAttackerMoneyReward(),
                ],

                'defender' => [
                    'xp' => $duel->getDefenderXpReward(),
                    'money' => $duel->getDefenderMoneyReward(),
                ],
            ],

            'rating' => [
                'attacker' => [
                    'before' =>
                        $duel->getAttackerRatingBefore(),

                    'after' =>
                        $duel->getAttackerRatingAfter(),

                    'delta' =>
                        $duel->getAttackerRatingDelta(),
                ],

                'defender' => [
                    'before' =>
                        $duel->getDefenderRatingBefore(),

                    'after' =>
                        $duel->getDefenderRatingAfter(),

                    'delta' =>
                        $duel->getDefenderRatingDelta(),
                ],
            ],

            'snapshots' => [
                'attacker' =>
                    $duel->getAttackerSnapshot(),

                'defender' =>
                    $duel->getDefenderSnapshot(),
            ],

            'antiFarming' => is_array($replayData)
                ? ($replayData['antiFarming'] ?? null)
                : null,

            'replayData' => $replayData,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCar(Car $car): array
    {
        $owner = $car->getUser();

        return [
            'id' => $car->getId(),
            'pilotName' => $car->getPilotName(),
            'color' => $car->getColor(),

            'level' => $car->getLevel(),
            'rating' => $car->getRating(),

            'wins' => $car->getWins(),
            'losses' => $car->getLosses(),

            'owner' => [
                'id' => $owner?->getId(),
                'email' => $owner?->getEmail(),
                'isActive' =>
                    $owner?->isActive() ?? false,
            ],
        ];
    }
}
