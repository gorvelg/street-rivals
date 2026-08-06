<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class AdminUsersController extends AbstractController
{
    #[Route(
        '',
        name: 'api_admin_users',
        methods: ['GET']
    )]
    public function __invoke(
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse {
        $page = max(
            1,
            $request->query->getInt('page', 1)
        );

        $itemsPerPage = $request->query->getInt(
            'itemsPerPage',
            20
        );

        $itemsPerPage = max(
            1,
            min($itemsPerPage, 100)
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

        $totalItems = $userRepository
            ->countForAdminSearch($search);

        $totalPages = max(
            1,
            (int) ceil(
                $totalItems / $itemsPerPage
            )
        );

        /*
         * Empêche de demander une page supérieure
         * au nombre de pages disponibles.
         */
        $page = min($page, $totalPages);

        $users = $userRepository->findAdminPage(
            search: $search,
            page: $page,
            itemsPerPage: $itemsPerPage,
        );

        return $this->json([
            'members' => $users,
            'pagination' => [
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
            ],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
