<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AdminUserDetailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class AdminUserDetailController extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'api_admin_user_detail',
        requirements: [
            'id' => '\d+',
        ],
        methods: ['GET']
    )]
    public function __invoke(
        int $id,
        UserRepository $userRepository,
        AdminUserDetailService $detailService,
    ): JsonResponse {
        $user = $userRepository->find($id);

        if (!$user instanceof User) {
            throw new NotFoundHttpException(
                'Utilisateur introuvable.'
            );
        }

        return $this->json(
            $detailService->getDetail($user)
        );
    }
}
