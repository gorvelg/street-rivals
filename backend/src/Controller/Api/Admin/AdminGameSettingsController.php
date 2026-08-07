<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\User;
use App\Service\GameSettingManager;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/game-settings')]
#[IsGranted('ROLE_ADMIN')]
final class AdminGameSettingsController
    extends AbstractController
{
    #[Route(
        '',
        name: 'api_admin_game_settings',
        methods: ['GET'],
    )]
    public function list(
        GameSettingManager $settingManager,
    ): JsonResponse {
        return $this->json([
            'members' =>
                $settingManager->getAllForAdmin(),
        ]);
    }

    #[Route(
        '',
        name: 'api_admin_game_settings_update',
        methods: ['PATCH'],
    )]
    public function update(
        Request $request,
        GameSettingManager $settingManager,
    ): JsonResponse {
        $administrator = $this->getUser();

        if (!$administrator instanceof User) {
            throw new \LogicException(
                'Administrateur non authentifié.',
            );
        }

        $payload = $this->decodePayload(
            $request,
        );

        $values = $payload['values']
            ?? null;

        if (!is_array($values)) {
            throw new BadRequestHttpException(
                'La propriété "values" doit être un objet JSON.',
            );
        }

        return $this->json(
            $settingManager->updateMany(
                values: $values,
                administrator: $administrator,
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
                'Le corps doit être un objet JSON.',
            );
        }

        return $payload;
    }
}
