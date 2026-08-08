<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Car;
use App\Entity\CarCard;
use App\Entity\User;
use App\Repository\CarCardRepository;
use App\Repository\CarRepository;
use App\Service\CarEquipmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class EquipCarCardController
    extends AbstractController
{
    #[Route(
        '/api/cars/{carId}/cards/{carCardId}/equip',
        name: 'api_car_card_equip',
        requirements: [
            'carId' => '\d+',
            'carCardId' => '\d+',
        ],
        methods: ['POST'],
    )]
    public function __invoke(
        int $carId,
        int $carCardId,
        CarRepository $carRepository,
        CarCardRepository $carCardRepository,
        CarEquipmentService $equipmentService,
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
            throw $this
                ->createAccessDeniedException(
                    'Vous ne pouvez pas modifier cette voiture.',
                );
        }

        $carCard = $carCardRepository
            ->find(
                $carCardId,
            );

        if (!$carCard instanceof CarCard) {
            throw new NotFoundHttpException(
                'Carte possédée introuvable.',
            );
        }

        return $this->json(
            $equipmentService->equip(
                car: $car,
                carCard: $carCard,
                user: $user,
            ),
        );
    }
}
