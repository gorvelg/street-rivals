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

    /**
     * Méthode autonome.
     *
     * Elle ouvre une transaction et est notamment utilisée
     * par la commande app:car:add-xp.
     */
    public function grantXp(Car $car, int $amount): ?CardChoice
    {
        return $this->entityManager->wrapInTransaction(
            function (
                EntityManagerInterface $entityManager
            ) use ($car, $amount): ?CardChoice {
                return $this->applyXp($car, $amount);
            }
        );
    }

    /**
     * Applique l’XP sans ouvrir de transaction et sans flush.
     *
     * Cette méthode est utilisée lorsqu’une transaction plus globale
     * est déjà ouverte, notamment pendant la création d’un duel.
     */
    public function applyXp(Car $car, int $amount): ?CardChoice
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException(
                'Le montant d’XP doit être supérieur à zéro.'
            );
        }

        $car->addXp($amount);

        $choice = $this->processNextLevelIfPossible($car);

        $this->entityManager->persist($car);

        return $choice;
    }

    public function processNextLevelIfPossible(
        Car $car,
        ?CardChoice $ignoredChoice = null,
    ): ?CardChoice {
        if (!$car->canLevelUp()) {
            return null;
        }

        $pendingChoice = $this->cardChoiceRepository
            ->findPendingForCar(
                $car,
                $ignoredChoice?->getId()
            );

        /*
         * L’XP reste stockée, mais la voiture doit choisir
         * sa carte actuelle avant de monter de nouveau.
         */
        if ($pendingChoice instanceof CardChoice) {
            return null;
        }

        $car->levelUp();

        return $this->cardChoiceGenerator
            ->generateForCar($car);
    }
}
