<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\Card;
use App\Repository\CardRepository;
use App\Repository\CarCardRepository;
use App\Service\AdminCardDetailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/cards')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCardDetailController
    extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'api_admin_card_detail',
        requirements: [
            'id' => '\d+',
        ],
        methods: ['GET']
    )]
    public function __invoke(
        int $id,
        Request $request,
        CardRepository $cardRepository,
        CarCardRepository $carCardRepository,
        AdminCardDetailService $detailService,
    ): JsonResponse {
        $card = $cardRepository->find($id);

        if (!$card instanceof Card) {
            throw new NotFoundHttpException(
                'Carte introuvable.'
            );
        }

        $page = max(
            1,
            $request->query->getInt(
                'page',
                1
            )
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

        $searchValue = trim(
            (string) $request->query->get(
                'search',
                ''
            )
        );

        $search = $searchValue !== ''
            ? $searchValue
            : null;

        $tierValue = $request->query->get(
            'tier'
        );

        $tier = is_numeric($tierValue)
            ? (int) $tierValue
            : null;

        if (
            $tier !== null
            && !in_array(
                $tier,
                [1, 2, 3],
                true
            )
        ) {
            $tier = null;
        }

        $equippedOnly = filter_var(
            $request->query->get(
                'equippedOnly',
                false
            ),
            FILTER_VALIDATE_BOOL
        );

        $totalItems = $carCardRepository
            ->countForAdminCard(
                card: $card,
                search: $search,
                tier: $tier,
                equippedOnly: $equippedOnly,
            );

        $totalPages = max(
            1,
            (int) ceil(
                $totalItems / $itemsPerPage
            )
        );

        $page = min(
            $page,
            $totalPages
        );

        return $this->json(
            $detailService->getDetail(
                card: $card,
                search: $search,
                tier: $tier,
                equippedOnly: $equippedOnly,
                page: $page,
                itemsPerPage: $itemsPerPage,
                totalItems: $totalItems,
                totalPages: $totalPages,
            )
        );
    }
}
