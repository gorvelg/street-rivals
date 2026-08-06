<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\Duel;
use App\Repository\DuelRepository;
use App\Service\AdminDuelDetailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/duels')]
#[IsGranted('ROLE_ADMIN')]
final class AdminDuelDetailController extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'api_admin_duel_detail',
        requirements: [
            'id' => '\d+',
        ],
        methods: ['GET']
    )]
    public function __invoke(
        int $id,
        DuelRepository $duelRepository,
        AdminDuelDetailService $detailService,
    ): JsonResponse {
        $duel = $duelRepository->find($id);

        if (!$duel instanceof Duel) {
            throw new NotFoundHttpException(
                'Duel introuvable.'
            );
        }

        return $this->json(
            $detailService->getDetail($duel)
        );
    }
}
