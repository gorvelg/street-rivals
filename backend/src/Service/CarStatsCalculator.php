<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CarStatsOutput;
use App\Entity\Car;
use App\Entity\CarCard;
use App\Enum\CardKind;
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

    public function calculate(
        Car $car,
    ): CarStatsOutput {
        /*
         * Statistiques natives de la voiture.
         *
         * Elles ne sont jamais modifiées directement
         * par les cartes.
         */
        $base = [
            'speed' =>
                $car->getSpeed(),

            'acceleration' =>
                $car->getAcceleration(),

            'grip' =>
                $car->getGrip(),

            'solidity' =>
                $car->getSolidity(),
        ];

        /*
         * Somme de tous les bonus et malus provenant
         * des cartes.
         */
        $bonuses = [
            'speed' => 0,
            'acceleration' => 0,
            'grip' => 0,
            'solidity' => 0,
        ];

        /*
         * Liste détaillée des cartes ayant participé
         * au calcul.
         */
        $appliedCards = [];

        /*
         * IMPORTANT :
         *
         * On ne récupère plus uniquement les cartes
         * équipées.
         *
         * Les STAT_BOOST doivent être appliqués même
         * lorsqu'ils ne sont pas équipés.
         */
        /** @var list<CarCard> $carCards */
        $carCards = $this->carCardRepository
            ->findBy([
                'car' => $car,
            ]);

        foreach ($carCards as $carCard) {
            $card = $carCard->getCard();

            if ($card === null) {
                continue;
            }

            /*
             * Une carte désactivée globalement
             * ne produit aucun effet.
             */
            if (!$card->isEnabled()) {
                continue;
            }

            /*
             * Le traitement dépend maintenant
             * de la famille de la carte.
             */
            match ($card->getKind()) {
                CardKind::ABILITY =>
                $this->applyAbilityCard(
                    carCard: $carCard,
                    bonuses: $bonuses,
                    appliedCards: $appliedCards,
                ),

                CardKind::STAT_BOOST =>
                $this->applyStatCard(
                    carCard: $carCard,
                    bonuses: $bonuses,
                    appliedCards: $appliedCards,
                ),

                CardKind::EQUIPMENT =>
                $this->applyEquipmentCard(
                    carCard: $carCard,
                    bonuses: $bonuses,
                    appliedCards: $appliedCards,
                ),
            };
        }

        /*
         * Stats finales :
         *
         * base + bonus/malus.
         */
        $effective = [];

        foreach (
            self::AVAILABLE_STATS
            as $stat
        ) {
            $effective[$stat] =
                $base[$stat]
                + $bonuses[$stat];
        }

        return new CarStatsOutput(
            carId: $car->getId()
            ?? throw new \LogicException(
                'La voiture doit être enregistrée.',
            ),

            pilotName:
            $car->getPilotName()
            ?? '',

            level:
            $car->getLevel(),

            base:
            $base,

            bonuses:
            $bonuses,

            effective:
            $effective,

            appliedCards:
            $appliedCards,
        );
    }

    /**
     * Traitement des anciennes cartes ABILITY.
     *
     * On conserve le comportement existant :
     *
     * - la carte doit être équipée ;
     * - son type historique doit être PASSIVE ;
     * - CardEffectResolver reste utilisé ;
     * - seul un effet stat_bonus est appliqué ici.
     *
     * @param array<string, int|float> $bonuses
     * @param list<array<string, mixed>> $appliedCards
     */
    private function applyAbilityCard(
        CarCard $carCard,
        array &$bonuses,
        array &$appliedCards,
    ): void {
        $card = $carCard->getCard();

        if ($card === null) {
            return;
        }

        /*
         * Avant cette évolution,
         * CarStatsCalculator utilisait :
         *
         * findEquippedByCar()
         *
         * On reproduit donc exactement cette règle
         * pour les anciennes capacités.
         */
        if (!$carCard->isEquipped()) {
            return;
        }

        /*
         * Les cartes ACTIVE continuent d'être
         * traitées pendant les duels.
         */
        if ($card->getType() !== 'PASSIVE') {
            return;
        }

        $effect = $this->effectResolver
            ->resolve(
                $carCard,
            );

        /*
         * CarStatsCalculator ne traite ici que
         * les bonus permanents de statistiques.
         */
        if (
            ($effect['kind'] ?? null)
            !== 'stat_bonus'
        ) {
            return;
        }

        $stat = $effect['stat']
            ?? null;

        $value = $effect['value']
            ?? null;

        if (
            !is_string($stat)
            || !in_array(
                $stat,
                self::AVAILABLE_STATS,
                true,
            )
        ) {
            throw new \LogicException(
                sprintf(
                    'La statistique de la carte "%s" est invalide.',
                    $card->getName(),
                ),
            );
        }

        if (
            !is_int($value)
            && !is_float($value)
        ) {
            throw new \LogicException(
                sprintf(
                    'La valeur de la carte "%s" est invalide.',
                    $card->getName(),
                ),
            );
        }

        $integerValue =
            (int) $value;

        $bonuses[$stat] +=
            $integerValue;

        $appliedCards[] = [
            'carCardId' =>
                $carCard->getId(),

            'cardId' =>
                $card->getId(),

            'code' =>
                $card->getCode(),

            'name' =>
                $card->getName(),

            'kind' =>
                $card->getKind()->value,

            'tier' =>
                $carCard->getTier(),

            'equipped' =>
                $carCard->isEquipped(),

            'stat' =>
                $stat,

            'value' =>
                $integerValue,
        ];
    }

    /**
     * Un STAT_BOOST est toujours actif dès lors
     * que la voiture possède la carte.
     *
     * Exemple :
     *
     * {
     *   "tiers": {
     *     "1": {
     *       "speed": 2
     *     }
     *   }
     * }
     *
     * @param array<string, int|float> $bonuses
     * @param list<array<string, mixed>> $appliedCards
     */
    private function applyStatCard(
        CarCard $carCard,
        array &$bonuses,
        array &$appliedCards,
    ): void {
        $this->applyTierStatEffects(
            carCard: $carCard,
            bonuses: $bonuses,
            appliedCards: $appliedCards,
        );
    }

    /**
     * Un équipement ne produit ses bonus que
     * lorsqu'il est actuellement équipé.
     *
     * @param array<string, int|float> $bonuses
     * @param list<array<string, mixed>> $appliedCards
     */
    private function applyEquipmentCard(
        CarCard $carCard,
        array &$bonuses,
        array &$appliedCards,
    ): void {
        if (!$carCard->isEquipped()) {
            return;
        }

        $this->applyTierStatEffects(
            carCard: $carCard,
            bonuses: $bonuses,
            appliedCards: $appliedCards,
        );
    }

    /**
     * Applique les effets de statistiques présents
     * dans effectConfig pour le palier actuel
     * de la CarCard.
     *
     * Utilisé par :
     *
     * - STAT_BOOST
     * - EQUIPMENT
     *
     * @param array<string, int|float> $bonuses
     * @param list<array<string, mixed>> $appliedCards
     */
    private function applyTierStatEffects(
        CarCard $carCard,
        array &$bonuses,
        array &$appliedCards,
    ): void {
        $card = $carCard->getCard();

        if ($card === null) {
            return;
        }

        $tier = $carCard->getTier();

        if ($tier < 1) {
            throw new \LogicException(
                sprintf(
                    'Le palier de la carte "%s" est invalide.',
                    $card->getName(),
                ),
            );
        }

        if ($tier > $card->getMaxTier()) {
            throw new \LogicException(
                sprintf(
                    'La carte "%s" possède le palier %d alors que son palier maximal est %d.',
                    $card->getName(),
                    $tier,
                    $card->getMaxTier(),
                ),
            );
        }

        $effectConfig =
            $card->getEffectConfig()
            ?? [];

        $tiers =
            $effectConfig['tiers']
            ?? null;

        if (!is_array($tiers)) {
            throw new \LogicException(
                sprintf(
                    'La carte "%s" ne possède pas de configuration "tiers" valide.',
                    $card->getName(),
                ),
            );
        }

        $tierConfiguration =
            $tiers[(string) $tier]
            ?? $tiers[$tier]
            ?? null;

        if (!is_array($tierConfiguration)) {
            throw new \LogicException(
                sprintf(
                    'La configuration du palier %d de la carte "%s" est introuvable.',
                    $tier,
                    $card->getName(),
                ),
            );
        }

        foreach (
            $tierConfiguration
            as $stat => $value
        ) {
            if (
                !is_string($stat)
                || !in_array(
                    $stat,
                    self::AVAILABLE_STATS,
                    true,
                )
            ) {
                throw new \LogicException(
                    sprintf(
                        'La statistique "%s" de la carte "%s" est invalide.',
                        (string) $stat,
                        $card->getName(),
                    ),
                );
            }

            if (!is_int($value)) {
                throw new \LogicException(
                    sprintf(
                        'La valeur de "%s" pour la carte "%s" doit être un entier.',
                        $stat,
                        $card->getName(),
                    ),
                );
            }

            /*
             * Ajout du bonus ou du malus au total.
             */
            $bonuses[$stat] +=
                $value;

            /*
             * Une carte peut modifier plusieurs stats.
             *
             * On ajoute donc une ligne par statistique
             * afin de pouvoir afficher précisément
             * l'origine des bonus.
             */
            $appliedCards[] = [
                'carCardId' =>
                    $carCard->getId(),

                'cardId' =>
                    $card->getId(),

                'code' =>
                    $card->getCode(),

                'name' =>
                    $card->getName(),

                'kind' =>
                    $card->getKind()->value,

                'equipmentSlot' =>
                    $card
                        ->getEquipmentSlot()
                        ?->value,

                'tier' =>
                    $tier,

                'equipped' =>
                    $carCard->isEquipped(),

                'stat' =>
                    $stat,

                'value' =>
                    $value,
            ];
        }
    }
}
