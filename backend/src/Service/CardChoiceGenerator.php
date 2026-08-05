<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Entity\Card;
use App\Entity\CardChoice;
use App\Repository\CarCardRepository;
use App\Repository\CardChoiceRepository;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CardChoiceGenerator
{
    public function __construct(
        private readonly CardRepository $cardRepository,
        private readonly CarCardRepository $carCardRepository,
        private readonly CardChoiceRepository $cardChoiceRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function generateForCar(Car $car): CardChoice
    {
        $existingChoice = $this->cardChoiceRepository->findOneBy([
            'car' => $car,
            'level' => $car->getLevel(),
        ]);

        if ($existingChoice instanceof CardChoice) {
            throw new \DomainException(
                'Un choix de cartes existe déjà pour ce niveau.'
            );
        }

        /*
         * Seules les cartes ayant atteint leur palier maximal
         * sont exclues du tirage.
         *
         * Une carte déjà possédée au palier 1 ou 2 peut donc
         * être proposée afin de l’améliorer.
         */
        $maxedCardIds = [];

        foreach (
            $this->carCardRepository->findBy(['car' => $car])
            as $carCard
        ) {
            if ($carCard->canUpgrade()) {
                continue;
            }

            $cardId = $carCard->getCard()?->getId();

            if ($cardId !== null) {
                $maxedCardIds[$cardId] = true;
            }
        }

        $enabledCards = $this->cardRepository->findBy([
            'isEnabled' => true,
        ]);

        $eligibleCards = array_values(array_filter(
            $enabledCards,
            static function (Card $card) use ($maxedCardIds): bool {
                $cardId = $card->getId();

                return $cardId !== null
                    && !isset($maxedCardIds[$cardId]);
            }
        ));

        if (count($eligibleCards) < 2) {
            throw new \RuntimeException(
                'Il n’y a pas assez de cartes disponibles pour proposer un choix.'
            );
        }

        /*
         * Le tableau contient une seule occurrence de chaque carte.
         * Les deux cartes proposées seront donc toujours différentes.
         */
        shuffle($eligibleCards);

        $choice = new CardChoice(
            car: $car,
            level: $car->getLevel(),
            firstCard: $eligibleCards[0],
            secondCard: $eligibleCards[1],
        );

        $this->entityManager->persist($choice);

        return $choice;
    }
}
