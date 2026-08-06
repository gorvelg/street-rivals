<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\CarStatsOutput;
use App\Entity\User;
use App\Repository\CarRepository;
use App\Service\CarStatsCalculator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<CarStatsOutput>
 */
final class CarStatsProvider implements ProviderInterface
{
    public function __construct(
        private readonly CarRepository $carRepository,
        private readonly CarStatsCalculator $statsCalculator,
        private readonly Security $security,
    ) {
    }

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CarStatsOutput {
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

        return $this->statsCalculator->calculate($car);
    }
}
