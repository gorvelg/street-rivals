<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CarStatsOutput;
use App\Entity\Car;
use App\Repository\CarCardRepository;

final class CarStatsCalculator
{
    private const AVAILABLE_STATS = [
        'speed',
        'acceleration',
        'grip',
        'solidity',
    ];

    public function __construct(
        private readonly CarCardRepository $carCardRepository,
        private readonly CardEffectResolver $effectResolver,
    ) {
    }

    public function calculate(Car $car): CarStatsOutput
    {
        $base = [
            'speed' => $car->getSpeed(),
            'acceleration' => $car->getAcceleration(),
            'grip' => $car->getGrip(),
            'solidity' => $car->getSolidity(),
        ];

        $bonuses = [
            'speed' => 0,
            'acceleration' => 0,
            'grip' => 0,
            'solidity' => 0,
        ];

        $appliedCards = [];

        foreach ($this->carCardRepository->findEquippedByCar($car) as $carCard) {
            $card = $carCard->getCard();

            if ($card === null) {
                continue;
            }

            /*
             * Une carte désactivée globalement ne doit plus produire d’effet.
             */
            if (!$card->isEnabled()) {
                continue;
            }

            /*
             * Les cartes actives seront traitées pendant les duels.
             */
            if ($card->getType() !== 'PASSIVE') {
                continue;
            }

            $effect = $this->effectResolver->resolve($carCard);

            /*
             * Ce calcul ne traite que les bonus permanents.
             */
            if (($effect['kind'] ?? null) !== 'stat_bonus') {
                continue;
            }

            $stat = $effect['stat'] ?? null;
            $value = $effect['value'] ?? null;

            if (
                !is_string($stat)
                || !in_array($stat, self::AVAILABLE_STATS, true)
            ) {
                throw new \LogicException(sprintf(
                    'La statistique de la carte "%s" est invalide.',
                    $card->getName()
                ));
            }

            if (!is_int($value) && !is_float($value)) {
                throw new \LogicException(sprintf(
                    'La valeur de la carte "%s" est invalide.',
                    $card->getName()
                ));
            }

            $integerValue = (int) $value;

            $bonuses[$stat] += $integerValue;

            $appliedCards[] = [
                'carCardId' => $carCard->getId(),
                'cardId' => $card->getId(),
                'code' => $card->getCode(),
                'name' => $card->getName(),
                'tier' => $carCard->getTier(),
                'stat' => $stat,
                'value' => $integerValue,
            ];
        }

        $effective = [];

        foreach (self::AVAILABLE_STATS as $stat) {
            $effective[$stat] = $base[$stat] + $bonuses[$stat];
        }

        return new CarStatsOutput(
            carId: $car->getId()
            ?? throw new \LogicException(
                'La voiture doit être enregistrée.'
            ),
            pilotName: $car->getPilotName() ?? '',
            level: $car->getLevel(),
            base: $base,
            bonuses: $bonuses,
            effective: $effective,
            appliedCards: $appliedCards,
        );
    }
}
