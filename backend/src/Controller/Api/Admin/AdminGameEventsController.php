<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Enum\GameEventType;
use App\Repository\GameEventRepository;
use App\Service\AdminGameEventSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/events')]
#[IsGranted('ROLE_ADMIN')]
final class AdminGameEventsController
    extends AbstractController
{
    #[Route(
        '',
        name: 'api_admin_events',
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
        GameEventRepository $eventRepository,
        AdminGameEventSerializer $serializer,
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

        $searchValue = trim(
            (string) $request->query->get(
                'search',
                '',
            ),
        );

        $search = $searchValue !== ''
            ? $searchValue
            : null;

        $typeValue = trim(
            (string) $request->query->get(
                'type',
                '',
            ),
        );

        $type = null;

        if ($typeValue !== '') {
            $type = GameEventType::tryFrom(
                $typeValue,
            );

            if (
                !$type instanceof GameEventType
            ) {
                throw new BadRequestHttpException(
                    'Le type d’événement est invalide.',
                );
            }
        }

        $dateFrom = $this->parseDate(
            value: $request->query->get(
                'dateFrom',
            ),
            parameterName: 'dateFrom',
        );

        $dateTo = $this->parseDate(
            value: $request->query->get(
                'dateTo',
            ),
            parameterName: 'dateTo',
        );

        $dateToExclusive = $dateTo?->modify(
            '+1 day',
        );

        if (
            $dateFrom !== null
            && $dateTo !== null
            && $dateFrom > $dateTo
        ) {
            throw new BadRequestHttpException(
                'La date de début doit précéder la date de fin.',
            );
        }

        $totalItems = $eventRepository
            ->countForAdminFilters(
                search: $search,
                type: $type,
                dateFrom: $dateFrom,
                dateToExclusive: $dateToExclusive,
            );

        $totalPages = max(
            1,
            (int) ceil(
                $totalItems / $itemsPerPage,
            ),
        );

        $page = min(
            $page,
            $totalPages,
        );

        $events = $eventRepository
            ->findAdminPage(
                search: $search,
                type: $type,
                dateFrom: $dateFrom,
                dateToExclusive: $dateToExclusive,
                page: $page,
                itemsPerPage: $itemsPerPage,
            );

        return $this->json([
            'members' => array_map(
                fn ($event): array =>
                $serializer->serialize($event),
                $events,
            ),

            'pagination' => [
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
            ],

            'filters' => [
                'search' => $search,
                'type' => $type?->value,
                'dateFrom' =>
                    $dateFrom?->format('Y-m-d'),
                'dateTo' =>
                    $dateTo?->format('Y-m-d'),
            ],

            'options' => [
                'types' => array_map(
                    static fn (
                        GameEventType $eventType,
                    ): string => $eventType->value,
                    GameEventType::cases(),
                ),
            ],
        ]);
    }

    private function parseDate(
        mixed $value,
        string $parameterName,
    ): ?\DateTimeImmutable {
        if (
            !is_string($value)
            || trim($value) === ''
        ) {
            return null;
        }

        $normalizedValue = trim($value);

        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $normalizedValue,
            new \DateTimeZone('UTC'),
        );

        $errors = \DateTimeImmutable::getLastErrors();

        if (
            !$date instanceof \DateTimeImmutable
            || (
                is_array($errors)
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
        ) {
            throw new BadRequestHttpException(
                sprintf(
                    'Le paramètre %s doit respecter le format YYYY-MM-DD.',
                    $parameterName,
                ),
            );
        }

        return $date;
    }
}
