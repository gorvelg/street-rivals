<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\RankingEntryOutput;
use App\Entity\User;
use App\Repository\CarRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<RankingEntryOutput>
 */
final class RankingProvider implements ProviderInterface
{
    private const MAXIMUM_RESULTS = 100;

    public function __construct(
        private readonly CarRepository $carRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @return list<RankingEntryOutput>
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): array {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.'
            );
        }

        $cars = $this->carRepository->findLeaderboard(
            self::MAXIMUM_RESULTS
        );

        $ranking = [];

        foreach ($cars as $index => $car) {
            $carId = $car->getId();

            if ($carId === null) {
                continue;
            }

            $ranking[] = new RankingEntryOutput(
                rank: $index + 1,
                carId: $carId,
                pilotName: $car->getPilotName() ?? '',
                color: $car->getColor() ?? '#000000',
                level: $car->getLevel(),
                rating: $car->getRating(),
                wins: $car->getWins(),
                losses: $car->getLosses(),
                duelsPlayed: $car->getDuelsPlayed(),
                winRate: $car->getWinRate(),
                isCurrentUser:
                $car->getUser()?->getId()
                === $user->getId(),
            );
        }

        return $ranking;
    }
}
