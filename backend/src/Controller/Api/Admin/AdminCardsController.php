<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/cards')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCardsController
    extends AbstractController
{
    #[Route(
        '',
        name: 'api_admin_cards',
        methods: ['GET']
    )]
    public function __invoke(
        Request $request,
        CardRepository $cardRepository,
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

        $search = $this->getOptionalString(
            $request,
            'search'
        );

        $type = $this->getOptionalString(
            $request,
            'type'
        );

        $rarity = $this->getOptionalString(
            $request,
            'rarity'
        );

        $tierValue = $request->query->get(
            'tier'
        );

        $tier = is_numeric($tierValue)
            ? (int) $tierValue
            : null;

        if (
            $tier !== null
            && !in_array($tier, [1, 2, 3], true)
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

        $totalItems = $cardRepository
            ->countForAdminSearch(
                search: $search,
                type: $type,
                rarity: $rarity,
                tier: $tier,
                equippedOnly: $equippedOnly,
            );

        $totalPages = max(
            1,
            (int) ceil(
                $totalItems / $itemsPerPage
            )
        );

        $page = min($page, $totalPages);

        $cards = $cardRepository
            ->findAdminPage(
                search: $search,
                type: $type,
                rarity: $rarity,
                tier: $tier,
                equippedOnly: $equippedOnly,
                page: $page,
                itemsPerPage: $itemsPerPage,
            );

        return $this->json([
            'members' => $cards,

            'pagination' => [
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
            ],

            'filters' => [
                'search' => $search,
                'type' => $type,
                'rarity' => $rarity,
                'tier' => $tier,
                'equippedOnly' => $equippedOnly,
            ],

            'options' => [
                'types' =>
                    $cardRepository->findAdminTypes(),

                'rarities' =>
                    $cardRepository
                        ->findAdminRarities(),

                'tiers' => [1, 2, 3],
            ],
        ]);
    }

    private function getOptionalString(
        Request $request,
        string $parameter,
    ): ?string {
        $value = trim(
            (string) $request->query->get(
                $parameter,
                ''
            )
        );

        return $value !== ''
            ? $value
            : null;
    }
}
