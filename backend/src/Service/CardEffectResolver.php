<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CarCard;

final class CardEffectResolver
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(CarCard $carCard): array
    {
        $card = $carCard->getCard();

        if ($card === null) {
            throw new \LogicException(
                'La carte possédée ne contient aucune définition de carte.'
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

        $tierKey = (string) $carCard->getTier();
        $tierConfig = $tiers[$tierKey] ?? null;

        if (!is_array($tierConfig)) {
            throw new \LogicException(sprintf(
                'Le palier %d n’est pas configuré pour la carte "%s".',
                $carCard->getTier(),
                $card->getName()
            ));
        }

        return array_merge(
            [
                'kind' => $effectConfig['kind'] ?? null,
                'stat' => $effectConfig['stat'] ?? null,
                'event' => $effectConfig['event'] ?? null,
            ],
            $tierConfig
        );
    }
}
