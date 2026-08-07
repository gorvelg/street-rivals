<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Car;
use App\Entity\CarCard;
use App\Entity\User;
use App\Repository\CarCardRepository;
use App\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CarCardsInventoryController
    extends AbstractController
{
    #[Route(
        '/api/cars/{carId}/cards',
        name: 'api_car_cards_inventory',
        requirements: [
            'carId' => '\d+',
        ],
        methods: ['GET'],
    )]
    public function __invoke(
        int $carId,
        CarRepository $carRepository,
        CarCardRepository $carCardRepository,
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException(
                'Utilisateur non authentifié.',
            );
        }

        $car = $carRepository->find(
            $carId,
        );

        if (!$car instanceof Car) {
            throw new NotFoundHttpException(
                'Voiture introuvable.',
            );
        }

        if (
            $car->getUser()?->getId()
            !== $user->getId()
        ) {
            throw $this->createAccessDeniedException(
                'Cette voiture ne vous appartient pas.',
            );
        }

        /** @var list<CarCard> $carCards */
        $carCards = $carCardRepository
            ->findBy(
                [
                    'car' => $car,
                ],
                [
                    'id' => 'ASC',
                ],
            );

        return $this->json([
            'member' => array_map(
                static function (
                    CarCard $carCard,
                ): array {
                    $card = $carCard->getCard();

                    if ($card === null) {
                        throw new \LogicException(
                            'Une CarCard ne possède aucune Card.',
                        );
                    }

                    return [
                        'carCardId' =>
                            $carCard->getId(),

                        'tier' =>
                            $carCard->getTier(),

                        'equipped' =>
                            $carCard->isEquipped(),

                        'acquiredLevel' =>
                            $carCard->getAcquiredLevel(),

                        'card' => [
                            'id' =>
                                $card->getId(),

                            'code' =>
                                $card->getCode(),

                            'name' =>
                                $card->getName(),

                            'description' =>
                                $card->getDescription(),

                            'rarity' =>
                                $card->getRarity(),

                            'type' =>
                                $card->getType(),

                            'kind' =>
                                $card->getKind()->value,

                            'equipmentSlot' =>
                                $card
                                    ->getEquipmentSlot()
                                    ?->value,

                            'maxTier' =>
                                $card->getMaxTier(),

                            'enabled' =>
                                $card->isEnabled(),

                            'effectConfig' =>
                                $card->getEffectConfig()
                                ?? [],
                        ],
                    ];
                },
                $carCards,
            ),
        ]);
    }
}
