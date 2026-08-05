<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\SelectCardChoiceInput;
use App\Entity\CarCard;
use App\Entity\CardChoice;
use App\Entity\User;
use App\Repository\CarCardRepository;
use App\Repository\CardChoiceRepository;
use App\Repository\CardRepository;
use App\Service\CarProgressionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<SelectCardChoiceInput, CardChoice>
 */
final class SelectCardChoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly CardChoiceRepository $cardChoiceRepository,
        private readonly CardRepository $cardRepository,
        private readonly CarCardRepository $carCardRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CarProgressionService $carProgressionService,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CardChoice {
        if (
            !$data instanceof SelectCardChoiceInput
            || $data->cardId === null
        ) {
            throw new BadRequestHttpException(
                'L’identifiant de la carte est obligatoire.'
            );
        }

        $choiceId = filter_var(
            $uriVariables['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($choiceId === false || $choiceId === null) {
            throw new BadRequestHttpException(
                'L’identifiant du choix est invalide.'
            );
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.'
            );
        }

        $choice = $this->cardChoiceRepository->find($choiceId);

        if (!$choice instanceof CardChoice) {
            throw new NotFoundHttpException(
                'Choix de cartes introuvable.'
            );
        }

        $car = $choice->getCar();

        if ($car === null) {
            throw new \LogicException(
                'Le choix de cartes ne possède aucune voiture.'
            );
        }

        if ($car->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException(
                'Ce choix ne vous appartient pas.'
            );
        }

        if (!$choice->isPending()) {
            throw new ConflictHttpException(
                'Une carte a déjà été sélectionnée.'
            );
        }

        $card = $this->cardRepository->find($data->cardId);

        if ($card === null) {
            throw new NotFoundHttpException(
                'Carte introuvable.'
            );
        }

        if (!$choice->containsCard($card)) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Cette carte ne fait pas partie des cartes proposées.'
            );
        }

        $existingCarCard = $this->carCardRepository->findOneBy([
            'car' => $car,
            'card' => $card,
        ]);

        /*
         * La voiture possède déjà la carte :
         * on augmente son palier.
         */
        if ($existingCarCard instanceof CarCard) {
            if (!$existingCarCard->canUpgrade()) {
                throw new ConflictHttpException(
                    'Cette carte a déjà atteint son palier maximal.'
                );
            }

            $existingCarCard->upgrade();
        } else {
            /*
             * Première acquisition :
             * création de la carte au palier 1.
             */
            $carCard = new CarCard();

            $carCard
                ->setCar($car)
                ->setCard($card)
                ->setEquipped(true)
                ->setAcquiredLevel($choice->getLevel());

            $this->entityManager->persist($carCard);
        }

        /*
         * Le choix est désormais définitif.
         */
        $choice->selectCard($card);

        /*
         * Si la voiture possède assez d’XP pour monter de nouveau,
         * la progression se poursuit après la résolution du choix.
         */
        $this->carProgressionService->processNextLevelIfPossible(
            $car,
            $choice
        );

        $this->entityManager->flush();

        return $choice;
    }
}
