<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Entity\CardChoice;
use App\Enum\GameEventType;
use App\Repository\CardChoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CarProgressionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CardChoiceRepository $cardChoiceRepository,
        private readonly CardChoiceGenerator $cardChoiceGenerator,
        private readonly GameEventTracker $eventTracker,
    ) {
    }

    /**
     * Méthode autonome.
     *
     * Elle ouvre une transaction et est notamment utilisée
     * par la commande app:car:add-xp.
     */
    public function grantXp(
        Car $car,
        int $amount,
    ): ?CardChoice {
        return $this->entityManager->wrapInTransaction(
            function (
                EntityManagerInterface $entityManager
            ) use ($car, $amount): ?CardChoice {
                return $this->applyXp(
                    car: $car,
                    amount: $amount,
                );
            }
        );
    }

    /**
     * Applique l’XP sans ouvrir de transaction et sans flush.
     *
     * Cette méthode est utilisée lorsqu’une transaction plus globale
     * est déjà ouverte, notamment pendant la création d’un duel.
     */
    public function applyXp(
        Car $car,
        int $amount,
    ): ?CardChoice {
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

        $previousLevel = $car->getLevel();
        $previousXp = $car->getXp();

        $car->levelUp();

        /*
         * track() ne fait aucun flush.
         *
         * L’événement sera enregistré avec la voiture,
         * le choix de carte et le duel éventuel dans la
         * transaction déjà en cours.
         */
        $this->eventTracker->track(
            type: GameEventType::LEVEL_UP,
            user: $car->getUser(),
            car: $car,
            payload: [
                'previousLevel' => $previousLevel,
                'newLevel' => $car->getLevel(),
                'xpBeforeLevelUp' => $previousXp,
                'remainingXp' => $car->getXp(),
                'xpRequiredForLevel' =>
                    $this->getXpRequiredForLevel(
                        $previousLevel
                    ),
            ],
        );

        return $this->cardChoiceGenerator
            ->generateForCar($car);
    }

    private function getXpRequiredForLevel(
        int $level,
    ): int {
        return 100 + (($level - 1) * 50);
    }
}
