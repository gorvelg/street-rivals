<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Repository\DuelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/duels')]
#[IsGranted('ROLE_ADMIN')]
final class AdminDuelsController extends AbstractController
{
    #[Route(
        '',
        name: 'api_admin_duels',
        methods: ['GET']
    )]
    public function __invoke(
        Request $request,
        DuelRepository $duelRepository,
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

        $winnerSide = trim(
            (string) $request->query->get(
                'winnerSide',
                ''
            )
        );

        if (
            !in_array(
                $winnerSide,
                ['attacker', 'defender'],
                true
            )
        ) {
            $winnerSide = null;
        }

        $engineVersion = trim(
            (string) $request->query->get(
                'engineVersion',
                ''
            )
        );

        $engineVersion = $engineVersion !== ''
            ? $engineVersion
            : null;

        $totalItems = $duelRepository
            ->countForAdminSearch(
                search: $search,
                winnerSide: $winnerSide,
                engineVersion: $engineVersion,
            );

        $totalPages = max(
            1,
            (int) ceil(
                $totalItems / $itemsPerPage
            )
        );

        $page = min($page, $totalPages);

        $duels = $duelRepository->findAdminPage(
            search: $search,
            winnerSide: $winnerSide,
            engineVersion: $engineVersion,
            page: $page,
            itemsPerPage: $itemsPerPage,
        );

        return $this->json([
            'members' => $duels,

            'pagination' => [
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
            ],

            'filters' => [
                'search' => $search,
                'winnerSide' => $winnerSide,
                'engineVersion' => $engineVersion,
            ],
        ]);
    }
}
