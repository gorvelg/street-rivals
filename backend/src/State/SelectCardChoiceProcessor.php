<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\SelectCardChoiceInput;
use App\Entity\CarCard;
use App\Entity\CardChoice;
use App\Entity\User;
use App\Enum\GameEventType;
use App\Repository\CarCardRepository;
use App\Repository\CardChoiceRepository;
use App\Repository\CardRepository;
use App\Service\CarProgressionService;
use App\Service\GameEventTracker;
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
        private readonly GameEventTracker $eventTracker,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CardChoice {
        /*
         * Vérification des données reçues.
         */
        if (
            !$data instanceof SelectCardChoiceInput
            || $data->cardId === null
        ) {
            throw new BadRequestHttpException(
                'L’identifiant de la carte est obligatoire.'
            );
        }

        /*
         * Vérification de l’identifiant du choix présent dans l’URL.
         */
        $choiceId = filter_var(
            $uriVariables['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($choiceId === false || $choiceId === null) {
            throw new BadRequestHttpException(
                'L’identifiant du choix est invalide.'
            );
        }

        /*
         * Vérification de l’utilisateur connecté.
         */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.'
            );
        }

        /*
         * Chargement du choix de cartes.
         */
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

        /*
         * Le choix doit appartenir à une voiture du joueur connecté.
         */
        if ($car->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException(
                'Ce choix ne vous appartient pas.'
            );
        }

        /*
         * Un choix déjà résolu ne peut pas être rejoué.
         */
        if (!$choice->isPending()) {
            throw new ConflictHttpException(
                'Une carte a déjà été sélectionnée.'
            );
        }

        /*
         * Chargement de la carte sélectionnée.
         */
        $card = $this->cardRepository->find($data->cardId);

        if ($card === null) {
            throw new NotFoundHttpException(
                'Carte introuvable.'
            );
        }

        /*
         * La carte doit obligatoirement faire partie
         * des deux propositions du choix.
         */
        if (!$choice->containsCard($card)) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Cette carte ne fait pas partie des cartes proposées.'
            );
        }

        /*
         * Recherche d’une éventuelle acquisition existante.
         */
        $existingCarCard = $this->carCardRepository->findOneBy([
            'car' => $car,
            'card' => $card,
        ]);

        $previousTier = 0;
        $newTier = 1;
        $eventType = GameEventType::CARD_SELECTED;

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

            $previousTier = $existingCarCard->getTier();

            $existingCarCard->upgrade();

            $newTier = $existingCarCard->getTier();
            $eventType = GameEventType::CARD_UPGRADED;
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
         * Le choix devient définitif.
         */
        $choice->selectCard($card);

        /*
         * Enregistrement de l’événement analytique.
         *
         * track() ne déclenche aucun flush.
         * L’événement sera enregistré en même temps que le choix,
         * la CarCard et l’éventuelle nouvelle montée de niveau.
         */
        $this->eventTracker->track(
            type: $eventType,
            user: $user,
            car: $car,
            payload: [
                'cardChoiceId' => $choice->getId(),
                'cardId' => $card->getId(),
                'cardCode' => $card->getCode(),
                'cardName' => $card->getName(),
                'cardType' => $card->getType(),
                'cardRarity' => $card->getRarity(),
                'choiceLevel' => $choice->getLevel(),
                'previousTier' => $previousTier,
                'newTier' => $newTier,
                'wasUpgrade' =>
                    $eventType === GameEventType::CARD_UPGRADED,
            ],
        );

        /*
         * Si la voiture possède encore assez d’XP pour monter,
         * la progression reprend après la résolution du choix.
         *
         * Une nouvelle montée de niveau peut générer :
         * - un nouvel événement level_up ;
         * - un nouveau CardChoice.
         */
        $this->carProgressionService
            ->processNextLevelIfPossible(
                $car,
                $choice
            );

        /*
         * Enregistrement de l’ensemble :
         *
         * - choix sélectionné ;
         * - nouvelle CarCard ou palier amélioré ;
         * - événement card_selected ou card_upgraded ;
         * - éventuelle montée de niveau ;
         * - éventuel nouveau choix de cartes.
         */
        $this->entityManager->flush();

        return $choice;
    }
}
