<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\MatchmakingOpponentOutput;
use App\Entity\User;
use App\Repository\CarRepository;
use App\Service\MatchmakingService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<MatchmakingOpponentOutput>
 */
final class MatchmakingOpponentProvider implements ProviderInterface
{
    public function __construct(
        private readonly CarRepository $carRepository,
        private readonly MatchmakingService $matchmakingService,
        private readonly Security $security,
    ) {
    }

    /**
     * @return list<MatchmakingOpponentOutput>
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): array {
        $carId = filter_var(
            $uriVariables['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($carId === false || $carId === null) {
            throw new BadRequestHttpException(
                'L’identifiant de la voiture est invalide.'
            );
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.'
            );
        }

        $car = $this->carRepository->find($carId);

        if ($car === null) {
            throw new NotFoundHttpException(
                'Voiture introuvable.'
            );
        }

        if ($car->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException(
                'Cette voiture ne vous appartient pas.'
            );
        }

        return $this->matchmakingService
            ->findOpponents($car);
    }
}
