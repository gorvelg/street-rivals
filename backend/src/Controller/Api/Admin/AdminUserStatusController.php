<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class AdminUserStatusController extends AbstractController
{
    #[Route(
        '/{id}/status',
        name: 'api_admin_user_status',
        requirements: [
            'id' => '\d+',
        ],
        methods: ['PATCH']
    )]
    public function __invoke(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            throw new BadRequestHttpException(
                'Le JSON transmis est invalide.'
            );
        }

        if (
            !array_key_exists('isActive', $data)
            || !is_bool($data['isActive'])
        ) {
            throw new BadRequestHttpException(
                'Le champ booléen "isActive" est obligatoire.'
            );
        }

        $targetUser = $userRepository->find($id);

        if (!$targetUser instanceof User) {
            throw new NotFoundHttpException(
                'Utilisateur introuvable.'
            );
        }

        $authenticatedUser = $this->getUser();

        if (!$authenticatedUser instanceof User) {
            throw new \LogicException(
                'Administrateur non authentifié.'
            );
        }

        /*
         * Empêche l’administrateur de désactiver
         * son propre compte.
         */
        if (
            $targetUser->getId()
            === $authenticatedUser->getId()
            && $data['isActive'] === false
        ) {
            throw new ConflictHttpException(
                'Vous ne pouvez pas désactiver votre propre compte.'
            );
        }

        $targetUser->setIsActive(
            $data['isActive']
        );

        $entityManager->flush();

        return $this->json([
            'id' => $targetUser->getId(),
            'email' => $targetUser->getEmail(),
            'roles' => $targetUser->getRoles(),
            'isActive' => $targetUser->isActive(),
        ]);
    }
}
