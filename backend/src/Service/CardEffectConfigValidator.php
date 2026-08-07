<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Card;
use App\Enum\CardKind;
use App\Enum\CarStat;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class CardEffectConfigValidator
{
    private const MIN_STAT_MODIFIER = -20;
    private const MAX_STAT_MODIFIER = 20;

    public function validate(
        Card $card,
    ): void {
        /*
         * Validation générale de Card :
         *
         * - equipment => equipmentSlot obligatoire
         * - autre type => equipmentSlot interdit
         * - maxTier entre 1 et 10
         */
        $this->validateCardConfiguration(
            $card,
        );

        /*
         * Les capacités existantes utilisent déjà
         * leur propre structure effectConfig.
         *
         * On ne les contraint donc pas pour le moment.
         */
        if ($card->getKind() === CardKind::ABILITY) {
            return;
        }

        /*
         * EQUIPMENT et STAT_BOOST utilisent
         * désormais la structure commune de
         * bonus de statistiques.
         */
        $this->validateStatEffectConfig(
            card: $card,
            effectConfig: $card->getEffectConfig(),
        );
    }

    private function validateCardConfiguration(
        Card $card,
    ): void {
        try {
            $card->validateConfiguration();
        } catch (
        \LogicException
        | \InvalidArgumentException $exception
        ) {
            throw new UnprocessableEntityHttpException(
                $exception->getMessage(),
                $exception,
            );
        }
    }

    /**
     * @param array<string, mixed> $effectConfig
     */
    private function validateStatEffectConfig(
        Card $card,
        array $effectConfig,
    ): void {
        if ($effectConfig === []) {
            throw new UnprocessableEntityHttpException(
                'La configuration d’effet ne peut pas être vide pour un équipement ou un bonus permanent.',
            );
        }

        if (!array_key_exists('tiers', $effectConfig)) {
            throw new UnprocessableEntityHttpException(
                'La configuration doit contenir une propriété "tiers".',
            );
        }

        $tiers = $effectConfig['tiers'];

        if (!is_array($tiers)) {
            throw new UnprocessableEntityHttpException(
                'La propriété "tiers" doit être un objet JSON.',
            );
        }

        if ($tiers === []) {
            throw new UnprocessableEntityHttpException(
                'La propriété "tiers" doit contenir au moins un palier.',
            );
        }

        $maxTier = $card->getMaxTier();

        /*
         * On exige tous les paliers entre 1 et maxTier.
         *
         * Exemple maxTier = 3 :
         *
         * 1
         * 2
         * 3
         */
        for ($tier = 1; $tier <= $maxTier; ++$tier) {
            $tierKey = (string) $tier;

            if (!array_key_exists($tierKey, $tiers)) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'Le palier %d est absent de la configuration.',
                        $tier,
                    ),
                );
            }

            $this->validateTier(
                tier: $tier,
                configuration: $tiers[$tierKey],
            );
        }

        /*
         * On refuse aussi les paliers supplémentaires.
         */
        foreach (array_keys($tiers) as $tierKey) {
            if (
                !is_int($tierKey)
                && !is_string($tierKey)
            ) {
                throw new UnprocessableEntityHttpException(
                    'Une clé de palier est invalide.',
                );
            }

            $tierNumber = filter_var(
                $tierKey,
                FILTER_VALIDATE_INT,
            );

            if (
                $tierNumber === false
                || $tierNumber < 1
                || $tierNumber > $maxTier
            ) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'Le palier "%s" est invalide. Les paliers autorisés vont de 1 à %d.',
                        (string) $tierKey,
                        $maxTier,
                    ),
                );
            }
        }

        /*
         * Pour ces nouvelles familles de cartes,
         * "tiers" est actuellement la seule clé
         * racine autorisée.
         */
        foreach (array_keys($effectConfig) as $rootKey) {
            if ($rootKey !== 'tiers') {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'La propriété "%s" n’est pas autorisée pour ce type de carte.',
                        (string) $rootKey,
                    ),
                );
            }
        }
    }

    private function validateTier(
        int $tier,
        mixed $configuration,
    ): void {
        if (!is_array($configuration)) {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    'La configuration du palier %d doit être un objet JSON.',
                    $tier,
                ),
            );
        }

        if ($configuration === []) {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    'Le palier %d doit modifier au moins une statistique.',
                    $tier,
                ),
            );
        }

        foreach (
            $configuration
            as $statName => $modifier
        ) {
            if (!is_string($statName)) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'Une statistique du palier %d possède un nom invalide.',
                        $tier,
                    ),
                );
            }

            $stat = CarStat::tryFrom(
                $statName,
            );

            if (!$stat instanceof CarStat) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'La statistique "%s" du palier %d est inconnue.',
                        $statName,
                        $tier,
                    ),
                );
            }

            if (!is_int($modifier)) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'La valeur de "%s" au palier %d doit être un nombre entier.',
                        $statName,
                        $tier,
                    ),
                );
            }

            if ($modifier === 0) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'La valeur de "%s" au palier %d ne peut pas être égale à 0.',
                        $statName,
                        $tier,
                    ),
                );
            }

            if (
                $modifier < self::MIN_STAT_MODIFIER
                || $modifier > self::MAX_STAT_MODIFIER
            ) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'La valeur de "%s" au palier %d doit être comprise entre %d et %d.',
                        $statName,
                        $tier,
                        self::MIN_STAT_MODIFIER,
                        self::MAX_STAT_MODIFIER,
                    ),
                );
            }
        }
    }
}
