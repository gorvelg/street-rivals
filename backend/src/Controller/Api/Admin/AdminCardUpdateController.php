<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\Card;
use App\Entity\User;
use App\Repository\CardRepository;
use App\Service\AdminCardUpdateService;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/cards')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCardUpdateController
    extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'api_admin_card_update',
        requirements: [
            'id' => '\d+',
        ],
        methods: ['PATCH'],
    )]
    public function __invoke(
        int $id,
        Request $request,
        CardRepository $cardRepository,
        AdminCardUpdateService $updateService,
    ): JsonResponse {
        $card = $cardRepository->find($id);

        if (!$card instanceof Card) {
            throw new NotFoundHttpException(
                'Carte introuvable.',
            );
        }

        $administrator = $this->getUser();

        if (!$administrator instanceof User) {
            throw new \LogicException(
                'Administrateur non authentifié.',
            );
        }

        $payload = $this->decodePayload(
            $request,
        );

        return $this->json(
            $updateService->update(
                card: $card,
                administrator: $administrator,
                payload: $payload,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(
        Request $request,
    ): array {
        $content = trim(
            $request->getContent(),
        );

        if ($content === '') {
            throw new BadRequestHttpException(
                'Le corps de la requête est vide.',
            );
        }

        try {
            $payload = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new BadRequestHttpException(
                'Le JSON envoyé est invalide.',
                $exception,
            );
        }

        if (!is_array($payload)) {
            throw new BadRequestHttpException(
                'Le corps de la requête doit être un objet JSON.',
            );
        }

        return $payload;
    }
}
