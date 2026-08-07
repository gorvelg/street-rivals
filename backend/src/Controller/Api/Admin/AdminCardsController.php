<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Enum\CardKind;
use App\Enum\EquipmentSlot;
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
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
        CardRepository $cardRepository,
    ): JsonResponse {
        $page = max(
            1,
            $request->query->getInt(
                'page',
                1,
            ),
        );

        $itemsPerPage = max(
            1,
            min(
                100,
                $request->query->getInt(
                    'itemsPerPage',
                    20,
                ),
            ),
        );

        $search =
            $this->getOptionalString(
                $request,
                'search',
            );

        $type =
            $this->getOptionalString(
                $request,
                'type',
            );

        $rarity =
            $this->getOptionalString(
                $request,
                'rarity',
            );

        /*
         * Nature :
         *
         * ability
         * equipment
         * stat_boost
         */
        $kindValue =
            $this->getOptionalString(
                $request,
                'kind',
            );

        $kind =
            $kindValue !== null
                ? CardKind::tryFrom(
                $kindValue,
            )
                : null;

        /*
         * Emplacement d'équipement.
         */
        $equipmentSlotValue =
            $this->getOptionalString(
                $request,
                'equipmentSlot',
            );

        $equipmentSlot =
            $equipmentSlotValue !== null
                ? EquipmentSlot::tryFrom(
                $equipmentSlotValue,
            )
                : null;

        /*
         * Les cartes supportent maintenant
         * des paliers de 1 à 10.
         */
        $tierValue =
            $request->query->get(
                'tier',
            );

        $tier =
            is_numeric($tierValue)
                ? (int) $tierValue
                : null;

        if (
            $tier !== null
            && (
                $tier < 1
                || $tier > 10
            )
        ) {
            $tier = null;
        }

        $equippedOnly =
            filter_var(
                $request->query->get(
                    'equippedOnly',
                    false,
                ),
                FILTER_VALIDATE_BOOL,
            );

        $totalItems =
            $cardRepository
                ->countForAdminSearch(
                    search: $search,
                    type: $type,
                    rarity: $rarity,
                    kind:
                    $kind?->value,
                    equipmentSlot:
                    $equipmentSlot?->value,
                    tier: $tier,
                    equippedOnly:
                    $equippedOnly,
                );

        $totalPages = max(
            1,
            (int) ceil(
                $totalItems
                / $itemsPerPage,
            ),
        );

        $page =
            min(
                $page,
                $totalPages,
            );

        $cards =
            $cardRepository
                ->findAdminPage(
                    search: $search,
                    type: $type,
                    rarity: $rarity,
                    kind:
                    $kind?->value,
                    equipmentSlot:
                    $equipmentSlot?->value,
                    tier: $tier,
                    equippedOnly:
                    $equippedOnly,
                    page: $page,
                    itemsPerPage:
                    $itemsPerPage,
                );

        return $this->json([
            'members' =>
                $cards,

            'pagination' => [
                'page' =>
                    $page,

                'itemsPerPage' =>
                    $itemsPerPage,

                'totalItems' =>
                    $totalItems,

                'totalPages' =>
                    $totalPages,
            ],

            'filters' => [
                'search' =>
                    $search,

                'type' =>
                    $type,

                'rarity' =>
                    $rarity,

                'kind' =>
                    $kind?->value,

                'equipmentSlot' =>
                    $equipmentSlot?->value,

                'tier' =>
                    $tier,

                'equippedOnly' =>
                    $equippedOnly,
            ],

            'options' => [
                'types' =>
                    $cardRepository
                        ->findAdminTypes(),

                'rarities' =>
                    $cardRepository
                        ->findAdminRarities(),

                'kinds' =>
                    array_map(
                        static fn (
                            CardKind $kind,
                        ): string =>
                        $kind->value,
                        CardKind::cases(),
                    ),

                'equipmentSlots' =>
                    array_map(
                        static fn (
                            EquipmentSlot $slot,
                        ): string =>
                        $slot->value,
                        EquipmentSlot::cases(),
                    ),

                'tiers' =>
                    range(
                        1,
                        10,
                    ),
            ],
        ]);
    }

    private function getOptionalString(
        Request $request,
        string $parameter,
    ): ?string {
        $value = trim(
            (string) $request
                ->query
                ->get(
                    $parameter,
                    '',
                ),
        );

        return $value !== ''
            ? $value
            : null;
    }
}
