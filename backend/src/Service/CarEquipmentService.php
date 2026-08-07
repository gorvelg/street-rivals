<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Entity\CarCard;
use App\Entity\User;
use App\Enum\CardKind;
use App\Enum\GameEventType;
use App\Repository\CarCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class CarEquipmentService
{
    public function __construct(
        private readonly CarCardRepository $carCardRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly GameEventTracker $eventTracker,
        private readonly CarStatsCalculator $carStatsCalculator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function equip(
        Car $car,
        CarCard $carCard,
        User $user,
    ): array {
        /*
         * Sécurité supplémentaire :
         * la voiture doit appartenir à l'utilisateur.
         */
        if (
            $car->getUser()?->getId()
            !== $user->getId()
        ) {
            throw new BadRequestHttpException(
                'Cette voiture ne vous appartient pas.',
            );
        }

        /*
         * La CarCard doit appartenir à la voiture.
         */
        if (
            $carCard->getCar()?->getId()
            !== $car->getId()
        ) {
            throw new BadRequestHttpException(
                'Cette carte n’appartient pas à cette voiture.',
            );
        }

        $card = $carCard->getCard();

        if ($card === null) {
            throw new \LogicException(
                'La carte associée est introuvable.',
            );
        }

        /*
         * Seules les cartes EQUIPMENT sont
         * équipables.
         */
        if (
            $card->getKind()
            !== CardKind::EQUIPMENT
        ) {
            throw new UnprocessableEntityHttpException(
                'Cette carte n’est pas un équipement.',
            );
        }

        /*
         * Un équipement doit obligatoirement
         * posséder un slot.
         */
        $equipmentSlot =
            $card->getEquipmentSlot();

        if ($equipmentSlot === null) {
            throw new \LogicException(
                sprintf(
                    'L’équipement "%s" ne possède aucun emplacement.',
                    $card->getName(),
                ),
            );
        }

        /*
         * Une carte désactivée dans l'administration
         * ne peut pas être équipée.
         */
        if (!$card->isEnabled()) {
            throw new UnprocessableEntityHttpException(
                'Cet équipement est actuellement désactivé.',
            );
        }

        /*
         * Si cet équipement est déjà équipé,
         * aucune modification n'est nécessaire.
         */
        if ($carCard->isEquipped()) {
            return [
                'updated' => false,

                'equipped' =>
                    $this->serializeCarCard(
                        $carCard,
                    ),

                'unequippedCarCardId' =>
                    null,

                'stats' =>
                    $this->carStatsCalculator
                        ->calculate($car),
            ];
        }

        return $this->entityManager
            ->wrapInTransaction(
                function () use (
                    $car,
                    $carCard,
                    $card,
                    $equipmentSlot,
                    $user,
                ): array {
                    /*
                     * On récupère toutes les cartes
                     * possédées par la voiture.
                     *
                     * On cherche ensuite un autre
                     * équipement actif utilisant
                     * le même emplacement.
                     */
                    /** @var list<CarCard> $carCards */
                    $carCards =
                        $this->carCardRepository
                            ->findBy([
                                'car' => $car,
                            ]);

                    $unequippedCarCard = null;

                    foreach (
                        $carCards
                        as $existingCarCard
                    ) {
                        /*
                         * On ignore la carte
                         * que l'on souhaite équiper.
                         */
                        if (
                            $existingCarCard->getId()
                            === $carCard->getId()
                        ) {
                            continue;
                        }

                        if (
                            !$existingCarCard
                                ->isEquipped()
                        ) {
                            continue;
                        }

                        $existingCard =
                            $existingCarCard
                                ->getCard();

                        if ($existingCard === null) {
                            continue;
                        }

                        /*
                         * On ne touche pas aux anciennes
                         * ABILITY éventuellement équipées.
                         */
                        if (
                            $existingCard->getKind()
                            !== CardKind::EQUIPMENT
                        ) {
                            continue;
                        }

                        /*
                         * On ne déséquipe que l'objet
                         * présent dans le même slot.
                         */
                        if (
                            $existingCard
                                ->getEquipmentSlot()
                            !== $equipmentSlot
                        ) {
                            continue;
                        }

                        $existingCarCard
                            ->setEquipped(false);

                        $unequippedCarCard =
                            $existingCarCard;

                        /*
                         * Il ne doit normalement exister
                         * qu'un seul équipement par slot.
                         *
                         * On ne break volontairement pas :
                         * si des données incohérentes
                         * existent déjà, toutes les cartes
                         * du même slot seront déséquipées.
                         */
                    }

                    /*
                     * Activation du nouvel équipement.
                     */
                    $carCard->setEquipped(true);

                    /*
                     * Trace de l'action.
                     */
                    $this->eventTracker->track(
                        type:
                        GameEventType::
                        EQUIPMENT_EQUIPPED,

                        user: $user,

                        car: $car,

                        payload: [
                            'carId' =>
                                $car->getId(),

                            'carCardId' =>
                                $carCard->getId(),

                            'cardId' =>
                                $card->getId(),

                            'cardCode' =>
                                $card->getCode(),

                            'cardName' =>
                                $card->getName(),

                            'equipmentSlot' =>
                                $equipmentSlot->value,

                            'unequippedCarCardId' =>
                                $unequippedCarCard
                                    ?->getId(),
                        ],
                    );

                    $this->entityManager
                        ->flush();

                    /*
                     * Maintenant que les changements
                     * sont enregistrés, on recalcule
                     * immédiatement les stats.
                     */
                    $stats =
                        $this->carStatsCalculator
                            ->calculate($car);

                    return [
                        'updated' => true,

                        'equipped' =>
                            $this->serializeCarCard(
                                $carCard,
                            ),

                        'unequippedCarCardId' =>
                            $unequippedCarCard
                                ?->getId(),

                        'stats' =>
                            $stats,
                    ];
                },
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCarCard(
        CarCard $carCard,
    ): array {
        $card = $carCard->getCard();

        if ($card === null) {
            throw new \LogicException(
                'La carte associée est introuvable.',
            );
        }

        return [
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
                $carCard->getTier(),

            'equipped' =>
                $carCard->isEquipped(),

            'effectConfig' =>
                $card->getEffectConfig()
                ?? [],
        ];
    }
}
