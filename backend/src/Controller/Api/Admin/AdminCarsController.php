<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/cars')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCarsController extends AbstractController
{
    #[Route(
        '',
        name: 'api_admin_cars',
        methods: ['GET']
    )]
    public function __invoke(
        Request $request,
        CarRepository $carRepository,
    ): JsonResponse {
        $page = max(
            1,
            $request->query->getInt('page', 1)
        );

        $itemsPerPage = max(
            1,
            min(
                100,
                $request->query->getInt(
                    'itemsPerPage',
                    20
                )
            )
        );

        $search = trim(
            (string) $request->query->get(
                'search',
                ''
            )
        );

        $search = $search !== ''
            ? $search
            : null;

        $minLevel = $this->getOptionalPositiveInteger(
            $request,
            'minLevel'
        );

        $minRating = $this->getOptionalPositiveInteger(
            $request,
            'minRating',
            allowZero: true,
        );

        $totalItems = $carRepository
            ->countForAdminSearch(
                search: $search,
                minLevel: $minLevel,
                minRating: $minRating,
            );

        $totalPages = max(
            1,
            (int) ceil(
                $totalItems / $itemsPerPage
            )
        );

        $page = min($page, $totalPages);

        $cars = $carRepository->findAdminPage(
            search: $search,
            minLevel: $minLevel,
            minRating: $minRating,
            page: $page,
            itemsPerPage: $itemsPerPage,
        );

        return $this->json([
            'members' => $cars,

            'pagination' => [
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
            ],

            'filters' => [
                'search' => $search,
                'minLevel' => $minLevel,
                'minRating' => $minRating,
            ],
        ]);
    }

    private function getOptionalPositiveInteger(
        Request $request,
        string $name,
        bool $allowZero = false,
    ): ?int {
        $rawValue = $request->query->get($name);

        if (
            $rawValue === null
            || $rawValue === ''
        ) {
            return null;
        }

        $value = filter_var(
            $rawValue,
            FILTER_VALIDATE_INT
        );

        if ($value === false) {
            return null;
        }

        if ($allowZero && $value === 0) {
            return 0;
        }

        return $value > 0
            ? $value
            : null;
    }
}
