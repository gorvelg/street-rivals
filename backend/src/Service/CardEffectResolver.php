<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CarCard;

final class CardEffectResolver
{
    /**
     * Renvoie la configuration correspondant au palier actuel.
     *
     * @return array<string, mixed>
     */
    public function resolve(CarCard $carCard): array
    {
        $card = $carCard->getCard();

        if ($card === null) {
            throw new \LogicException(
                'La carte possédée ne possède aucune définition.'
            );
        }

        $effectConfig = $card->getEffectConfig();
        $tiers = $effectConfig['tiers'] ?? null;

        if (!is_array($tiers)) {
            throw new \LogicException(sprintf(
                'La carte "%s" ne possède aucune configuration de paliers.',
                $card->getName()
            ));
        }

        $tier = $carCard->getTier();
        $tierConfig = $tiers[$tier] ?? $tiers[(string) $tier] ?? null;

        if (!is_array($tierConfig)) {
            throw new \LogicException(sprintf(
                'Le palier %d n’est pas configuré pour la carte "%s".',
                $tier,
                $card->getName()
            ));
        }

        /*
         * On retire le tableau complet des paliers,
         * puis on ajoute uniquement les valeurs du palier actuel.
         */
        unset($effectConfig['tiers']);

        return array_replace($effectConfig, $tierConfig);
    }
}
