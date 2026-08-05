<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\GenerateCardChoiceInput;
use App\Entity\Card;
use App\Entity\CardChoice;
use App\Entity\User;
use App\Repository\CarCardRepository;
use App\Repository\CardChoiceRepository;
use App\Repository\CardRepository;
use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<GenerateCardChoiceInput, CardChoice>
 */
final class GenerateCardChoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly CarRepository $carRepository,
        private readonly CardRepository $cardRepository,
        private readonly CarCardRepository $carCardRepository,
        private readonly CardChoiceRepository $cardChoiceRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CardChoice {
        if (
            !$data instanceof GenerateCardChoiceInput
            || $data->carId === null
        ) {
            throw new BadRequestHttpException(
                'L’identifiant de la voiture est obligatoire.'
            );
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.'
            );
        }

        $car = $this->carRepository->find($data->carId);

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

        $existingChoice = $this->cardChoiceRepository->findOneBy([
            'car' => $car,
            'level' => $car->getLevel(),
        ]);

        if ($existingChoice instanceof CardChoice) {
            throw new ConflictHttpException(
                'Un choix existe déjà pour ce niveau.'
            );
        }

        /*
         * Récupération des cartes déjà possédées par cette voiture.
         */
        $ownedCardIds = [];

        foreach ($this->carCardRepository->findBy(['car' => $car]) as $carCard) {
            $ownedCardId = $carCard->getCard()?->getId();

            if ($ownedCardId !== null) {
                $ownedCardIds[$ownedCardId] = true;
            }
        }

        /*
         * Pour le MVP :
         * - uniquement les cartes actives ;
         * - exclusion des cartes déjà possédées ;
         * - toutes les cartes ont la même probabilité.
         */
        $eligibleCards = array_values(array_filter(
            $this->cardRepository->findBy(['isEnabled' => true]),
            static function (Card $card) use ($ownedCardIds): bool {
                $cardId = $card->getId();

                return $cardId !== null
                    && !isset($ownedCardIds[$cardId]);
            }
        ));

        if (count($eligibleCards) < 2) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Il n’y a pas assez de cartes disponibles pour proposer un choix.'
            );
        }

        shuffle($eligibleCards);

        $firstCard = $eligibleCards[0];
        $secondCard = $eligibleCards[1];

        $choice = new CardChoice(
            car: $car,
            level: $car->getLevel(),
            firstCard: $firstCard,
            secondCard: $secondCard,
        );

        $this->entityManager->persist($choice);
        $this->entityManager->flush();

        return $choice;
    }
}
