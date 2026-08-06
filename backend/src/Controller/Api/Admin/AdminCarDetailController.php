<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\Car;
use App\Repository\CarRepository;
use App\Service\AdminCarDetailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/cars')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCarDetailController extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'api_admin_car_detail',
        requirements: [
            'id' => '\d+',
        ],
        methods: ['GET']
    )]
    public function __invoke(
        int $id,
        CarRepository $carRepository,
        AdminCarDetailService $detailService,
    ): JsonResponse {
        $car = $carRepository->find($id);

        if (!$car instanceof Car) {
            throw new NotFoundHttpException(
                'Voiture introuvable.'
            );
        }

        return $this->json(
            $detailService->getDetail($car)
        );
    }
}
