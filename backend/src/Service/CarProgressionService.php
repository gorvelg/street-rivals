<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Entity\CardChoice;
use App\Repository\CardChoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CarProgressionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CardChoiceRepository $cardChoiceRepository,
        private readonly CardChoiceGenerator $cardChoiceGenerator,
    ) {
    }

    public function grantXp(Car $car, int $amount): ?CardChoice
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException(
                'Le montant d’XP doit être supérieur à zéro.'
            );
        }

        return $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $entityManager) use (
                $car,
                $amount
            ): ?CardChoice {
                $car->addXp($amount);

                $choice = $this->processNextLevelIfPossible($car);

                $entityManager->persist($car);

                return $choice;
            }
        );
    }

    public function processNextLevelIfPossible(
        Car $car,
        ?CardChoice $ignoredChoice = null
    ): ?CardChoice {
        if (!$car->canLevelUp()) {
            return null;
        }

        $pendingChoice = $this->cardChoiceRepository
            ->findPendingForCar(
                $car,
                $ignoredChoice?->getId()
            );

        if ($pendingChoice instanceof CardChoice) {
            return null;
        }

        $car->levelUp();

        return $this->cardChoiceGenerator
            ->generateForCar($car);
    }
}
