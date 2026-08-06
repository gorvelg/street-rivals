<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\Car;
use App\Entity\User;
use App\Repository\CarRepository;
use App\Service\AdminCarCooldownService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/cars')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCarCooldownController
    extends AbstractController
{
    #[Route(
        '/{id}/reset-cooldown',
        name: 'api_admin_car_reset_cooldown',
        requirements: [
            'id' => '\d+',
        ],
        methods: ['POST']
    )]
    public function __invoke(
        int $id,
        CarRepository $carRepository,
        AdminCarCooldownService $cooldownService,
    ): JsonResponse {
        $car = $carRepository->find($id);

        if (!$car instanceof Car) {
            throw new NotFoundHttpException(
                'Voiture introuvable.'
            );
        }

        $administrator = $this->getUser();

        if (!$administrator instanceof User) {
            throw new \LogicException(
                'Administrateur non authentifié.'
            );
        }

        return $this->json(
            $cooldownService->reset(
                car: $car,
                administrator: $administrator,
            )
        );
    }
}
