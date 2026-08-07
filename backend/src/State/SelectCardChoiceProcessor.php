<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\SelectCardChoiceInput;
use App\Entity\Car;
use App\Entity\CarCard;
use App\Entity\Card;
use App\Entity\CardChoice;
use App\Entity\User;
use App\Enum\CardKind;
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
final class SelectCardChoiceProcessor
    implements ProcessorInterface
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
                'L’identifiant de la carte est obligatoire.',
            );
        }

        /*
         * Vérification de l'identifiant
         * du choix présent dans l'URL.
         */
        $choiceId = filter_var(
            $uriVariables['id'] ?? null,
            FILTER_VALIDATE_INT,
        );

        if (
            $choiceId === false
            || $choiceId === null
        ) {
            throw new BadRequestHttpException(
                'L’identifiant du choix est invalide.',
            );
        }

        /*
         * Utilisateur connecté.
         */
        $user = $this->security
            ->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.',
            );
        }

        /*
         * Chargement du CardChoice.
         */
        $choice =
            $this->cardChoiceRepository
                ->find($choiceId);

        if (!$choice instanceof CardChoice) {
            throw new NotFoundHttpException(
                'Choix de cartes introuvable.',
            );
        }

        $car = $choice->getCar();

        if (!$car instanceof Car) {
            throw new \LogicException(
                'Le choix de cartes ne possède aucune voiture.',
            );
        }

        /*
         * Le choix doit appartenir
         * au joueur connecté.
         */
        if (
            $car->getUser()?->getId()
            !== $user->getId()
        ) {
            throw new AccessDeniedHttpException(
                'Ce choix ne vous appartient pas.',
            );
        }

        /*
         * Un CardChoice résolu ne peut
         * plus être utilisé.
         */
        if (!$choice->isPending()) {
            throw new ConflictHttpException(
                'Une carte a déjà été sélectionnée.',
            );
        }

        /*
         * Chargement de la carte choisie.
         */
        $card = $this->cardRepository
            ->find(
                $data->cardId,
            );

        if (!$card instanceof Card) {
            throw new NotFoundHttpException(
                'Carte introuvable.',
            );
        }

        /*
         * La carte doit toujours faire partie
         * du choix proposé.
         */
        if (!$choice->containsCard($card)) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Cette carte ne fait pas partie des cartes proposées.',
            );
        }

        /*
         * Si une carte a été désactivée par
         * l'administration entre la génération
         * du choix et sa sélection, on refuse
         * désormais son acquisition.
         */
        if (!$card->isEnabled()) {
            throw new ConflictHttpException(
                'Cette carte n’est plus disponible.',
            );
        }

        /*
         * Validation générale du maxTier.
         */
        if ($card->getMaxTier() < 1) {
            throw new \LogicException(
                sprintf(
                    'La carte "%s" possède un palier maximal invalide.',
                    $card->getName(),
                ),
            );
        }

        /*
         * Vérification spécifique des équipements.
         */
        if (
            $card->getKind()
            === CardKind::EQUIPMENT
            && $card->getEquipmentSlot() === null
        ) {
            throw new \LogicException(
                sprintf(
                    'L’équipement "%s" ne possède aucun emplacement.',
                    $card->getName(),
                ),
            );
        }

        /*
         * Recherche d'une éventuelle
         * acquisition précédente.
         */
        $existingCarCard =
            $this->carCardRepository
                ->findOneBy([
                    'car' => $car,
                    'card' => $card,
                ]);

        $previousTier = 0;
        $newTier = 1;

        $eventType =
            GameEventType::CARD_SELECTED;

        $wasAutoEquipped = false;

        /*
         * ==========================================
         * CARTE DÉJÀ POSSÉDÉE
         * ==========================================
         *
         * On augmente simplement son palier.
         *
         * Son état equipped n'est jamais modifié.
         */
        if (
            $existingCarCard
            instanceof CarCard
        ) {
            if (
                !$existingCarCard
                    ->canUpgrade()
            ) {
                throw new ConflictHttpException(
                    sprintf(
                        'Cette carte a déjà atteint son palier maximal %d.',
                        $card->getMaxTier(),
                    ),
                );
            }

            $previousTier =
                $existingCarCard
                    ->getTier();

            $existingCarCard
                ->upgrade();

            $newTier =
                $existingCarCard
                    ->getTier();

            $eventType =
                GameEventType::CARD_UPGRADED;
        } else {
            /*
             * ======================================
             * PREMIÈRE ACQUISITION
             * ======================================
             */

            $carCard = new CarCard();

            $carCard
                ->setCar($car)
                ->setCard($card)
                ->setAcquiredLevel(
                    $choice->getLevel(),
                );

            /*
             * Le comportement initial dépend
             * maintenant de CardKind.
             */
            switch ($card->getKind()) {
                /*
                 * Les anciennes capacités
                 * conservent leur fonctionnement :
                 * elles sont équipées directement.
                 */
                case CardKind::ABILITY:
                    $carCard->setEquipped(
                        true,
                    );

                    break;

                /*
                 * Un STAT_BOOST est toujours pris
                 * en compte par CarStatsCalculator.
                 *
                 * isEquipped n'a donc aucune fonction
                 * pour cette famille.
                 */
                case CardKind::STAT_BOOST:
                    $carCard->setEquipped(
                        false,
                    );

                    break;

                /*
                 * Un nouvel équipement est
                 * automatiquement équipé uniquement
                 * si son emplacement est libre.
                 */
                case CardKind::EQUIPMENT:
                    $slotIsAvailable =
                        $this
                            ->isEquipmentSlotAvailable(
                                car: $car,
                                card: $card,
                            );

                    $carCard->setEquipped(
                        $slotIsAvailable,
                    );

                    $wasAutoEquipped =
                        $slotIsAvailable;

                    break;
            }

            $this->entityManager
                ->persist(
                    $carCard,
                );
        }

        /*
         * Le CardChoice devient définitif.
         */
        $choice->selectCard(
            $card,
        );

        /*
         * Historisation.
         */
        $this->eventTracker->track(
            type: $eventType,

            user: $user,

            car: $car,

            payload: [
                'cardChoiceId' =>
                    $choice->getId(),

                'cardId' =>
                    $card->getId(),

                'cardCode' =>
                    $card->getCode(),

                'cardName' =>
                    $card->getName(),

                /*
                 * Ancien type fonctionnel :
                 * PASSIVE / ACTIVE...
                 */
                'cardType' =>
                    $card->getType(),

                /*
                 * Nouvelle famille :
                 * ability / equipment / stat_boost
                 */
                'cardKind' =>
                    $card->getKind()->value,

                'equipmentSlot' =>
                    $card
                        ->getEquipmentSlot()
                        ?->value,

                'cardRarity' =>
                    $card->getRarity(),

                'choiceLevel' =>
                    $choice->getLevel(),

                'previousTier' =>
                    $previousTier,

                'newTier' =>
                    $newTier,

                'maxTier' =>
                    $card->getMaxTier(),

                'wasUpgrade' =>
                    $eventType
                    === GameEventType::CARD_UPGRADED,

                'wasAutoEquipped' =>
                    $wasAutoEquipped,
            ],
        );

        /*
         * Si suffisamment d'XP reste disponible,
         * on traite éventuellement le niveau suivant.
         */
        $this->carProgressionService
            ->processNextLevelIfPossible(
                $car,
                $choice,
            );

        /*
         * Sauvegarde de l'ensemble.
         */
        $this->entityManager
            ->flush();

        return $choice;
    }

    /**
     * Vérifie qu'aucun autre équipement actif
     * n'occupe déjà le slot de la nouvelle carte.
     */
    private function isEquipmentSlotAvailable(
        Car $car,
        Card $card,
    ): bool {
        if (
            $card->getKind()
            !== CardKind::EQUIPMENT
        ) {
            return false;
        }

        $equipmentSlot =
            $card->getEquipmentSlot();

        if ($equipmentSlot === null) {
            return false;
        }

        /** @var list<CarCard> $ownedCards */
        $ownedCards =
            $this->carCardRepository
                ->findBy([
                    'car' => $car,
                ]);

        foreach ($ownedCards as $ownedCarCard) {
            /*
             * Une carte non équipée
             * n'occupe aucun slot.
             */
            if (
                !$ownedCarCard
                    ->isEquipped()
            ) {
                continue;
            }

            $ownedCard =
                $ownedCarCard->getCard();

            if (!$ownedCard instanceof Card) {
                continue;
            }

            /*
             * Les anciennes ABILITY équipées
             * n'ont aucun rapport avec les
             * emplacements d'équipement.
             */
            if (
                $ownedCard->getKind()
                !== CardKind::EQUIPMENT
            ) {
                continue;
            }

            if (
                $ownedCard
                    ->getEquipmentSlot()
                === $equipmentSlot
            ) {
                return false;
            }
        }

        return true;
    }
}
