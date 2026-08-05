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

        if ($choice->getCar()?->getUser()?->getId() !== $user->getId()) {
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
            'car' => $choice->getCar(),
            'card' => $card,
        ]);

        if ($existingCarCard instanceof CarCard) {
            throw new ConflictHttpException(
                'Cette voiture possède déjà cette carte.'
            );
        }

        $choice->selectCard($card);

        $carCard = new CarCard();

        $carCard
            ->setCar($choice->getCar())
            ->setCard($card)
            ->setEquipped(true)
            ->setAcquiredLevel($choice->getLevel());

        $this->entityManager->persist($carCard);
        $this->entityManager->flush();

        return $choice;
    }
}
