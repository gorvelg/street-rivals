<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Service\AdminDashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminDashboardController extends AbstractController
{
    #[Route(
        '/dashboard',
        name: 'api_admin_dashboard',
        methods: ['GET']
    )]
    public function __invoke(
        AdminDashboardService $dashboardService,
    ): JsonResponse {
        return $this->json(
            $dashboardService->getDashboard()
        );
    }
}
