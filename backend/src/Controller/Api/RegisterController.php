<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    #[Route(
        '/api/register',
        name: 'api_register',
        methods: ['POST']
    )]
    public function __invoke(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            return $this->json([
                'error' => 'Le JSON transmis est invalide.',
            ], 400);
        }

        $email = mb_strtolower(
            trim((string) ($data['email'] ?? ''))
        );

        $plainPassword = (string) ($data['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => 'Adresse e-mail invalide.',
            ], 422);
        }

        if (mb_strlen($plainPassword) < 8) {
            return $this->json([
                'error' => 'Le mot de passe doit contenir au moins 8 caractères.',
            ], 422);
        }

        if ($userRepository->findOneBy(['email' => $email]) !== null) {
            return $this->json([
                'error' => 'Cette adresse e-mail est déjà utilisée.',
            ], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword(
            $passwordHasher->hashPassword($user, $plainPassword)
        );

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ], 201);
    }
}
