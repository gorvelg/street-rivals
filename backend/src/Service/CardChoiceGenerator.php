<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Entity\Card;
use App\Entity\CardChoice;
use App\Enum\CardKind;
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

    public function generateForCar(
        Car $car,
    ): CardChoice {
        /*
         * Une voiture ne peut avoir qu'un seul
         * choix de cartes pour un même niveau.
         */
        $existingChoice =
            $this->cardChoiceRepository
                ->findOneBy([
                    'car' => $car,
                    'level' => $car->getLevel(),
                ]);

        if ($existingChoice instanceof CardChoice) {
            throw new \DomainException(
                'Un choix de cartes existe déjà pour ce niveau.',
            );
        }

        /*
         * On détermine les cartes déjà arrivées
         * à leur palier maximal.
         *
         * IMPORTANT :
         *
         * On n'utilise volontairement plus
         * CarCard::canUpgrade() ici.
         *
         * Le palier maximal appartient maintenant
         * directement à Card via :
         *
         * Card::getMaxTier()
         *
         * Cela permet d'avoir par exemple :
         *
         * carte A -> maxTier 1
         * carte B -> maxTier 3
         * carte C -> maxTier 5
         */
        $maxedCardIds = [];

        $ownedCarCards =
            $this->carCardRepository
                ->findBy([
                    'car' => $car,
                ]);

        foreach ($ownedCarCards as $carCard) {
            $card = $carCard->getCard();

            if (!$card instanceof Card) {
                continue;
            }

            $cardId = $card->getId();

            if ($cardId === null) {
                continue;
            }

            /*
             * La carte peut encore être proposée
             * si son tier actuel est inférieur
             * au maxTier défini sur Card.
             */
            if (
                $carCard->getTier()
                < $card->getMaxTier()
            ) {
                continue;
            }

            /*
             * La carte est au maximum :
             * elle ne doit plus apparaître
             * dans les choix de niveau.
             */
            $maxedCardIds[$cardId] = true;
        }

        /*
         * On ne travaille qu'avec les cartes
         * activées globalement.
         */
        /** @var list<Card> $enabledCards */
        $enabledCards =
            $this->cardRepository
                ->findBy([
                    'isEnabled' => true,
                ]);

        /*
         * Suppression des cartes déjà arrivées
         * à leur palier maximal.
         */
        $eligibleCards =
            array_values(
                array_filter(
                    $enabledCards,

                    static function (
                        Card $card,
                    ) use (
                        $maxedCardIds,
                    ): bool {
                        $cardId =
                            $card->getId();

                        if ($cardId === null) {
                            return false;
                        }

                        return !isset(
                            $maxedCardIds[
                            $cardId
                            ],
                        );
                    },
                ),
            );

        /*
         * CardChoice nécessite actuellement
         * deux cartes.
         */
        if (count($eligibleCards) < 2) {
            throw new \RuntimeException(
                'Il n’y a pas assez de cartes disponibles pour proposer un choix.',
            );
        }

        /*
         * Mélange initial afin de conserver
         * le caractère aléatoire du système
         * existant.
         */
        shuffle(
            $eligibleCards,
        );

        /*
         * Première carte :
         * totalement aléatoire parmi les cartes
         * encore disponibles.
         */
        $firstCard =
            $eligibleCards[0];

        /*
         * On sélectionne ensuite une seconde carte
         * compatible avec la première.
         *
         * Deux règles sont privilégiées :
         *
         * 1. si possible, proposer une autre famille
         *    de carte afin de varier les choix ;
         *
         * 2. éviter deux équipements utilisant
         *    exactement le même emplacement.
         */
        $secondCard =
            $this->selectSecondCard(
                firstCard: $firstCard,
                eligibleCards: $eligibleCards,
            );

        $choice = new CardChoice(
            car: $car,

            level:
            $car->getLevel(),

            firstCard:
            $firstCard,

            secondCard:
            $secondCard,
        );

        $this->entityManager
            ->persist(
                $choice,
            );

        return $choice;
    }

    /**
     * @param list<Card> $eligibleCards
     */
    private function selectSecondCard(
        Card $firstCard,
        array $eligibleCards,
    ): Card {
        /*
         * Première passe :
         *
         * on cherche une carte :
         *
         * - différente ;
         * - compatible au niveau équipement ;
         * - appartenant de préférence à une
         *   autre famille.
         *
         * Exemple :
         *
         * firstCard = EQUIPMENT
         *
         * priorité :
         * STAT_BOOST ou ABILITY
         */
        foreach ($eligibleCards as $candidate) {
            if (
                $this->isSameCard(
                    firstCard: $firstCard,
                    secondCard: $candidate,
                )
            ) {
                continue;
            }

            if (
                !$this->areCardsCompatible(
                    firstCard: $firstCard,
                    secondCard: $candidate,
                )
            ) {
                continue;
            }

            if (
                $candidate->getKind()
                === $firstCard->getKind()
            ) {
                continue;
            }

            return $candidate;
        }

        /*
         * Deuxième passe :
         *
         * on accepte la même famille,
         * mais on continue d'éviter deux
         * équipements du même slot.
         *
         * Exemple autorisé :
         *
         * Moteur sportif
         * +
         * Pneus semi-slick
         *
         * Les deux sont EQUIPMENT mais les
         * slots sont différents.
         */
        foreach ($eligibleCards as $candidate) {
            if (
                $this->isSameCard(
                    firstCard: $firstCard,
                    secondCard: $candidate,
                )
            ) {
                continue;
            }

            if (
                !$this->areCardsCompatible(
                    firstCard: $firstCard,
                    secondCard: $candidate,
                )
            ) {
                continue;
            }

            return $candidate;
        }

        /*
         * Dernier fallback.
         *
         * Il peut arriver que toutes les cartes
         * restantes soient des équipements du
         * même emplacement.
         *
         * Comme la règle est "éviter autant
         * que possible" et non "interdire",
         * on autorise alors deux équipements
         * du même slot plutôt que de bloquer
         * totalement la montée de niveau.
         */
        foreach ($eligibleCards as $candidate) {
            if (
                !$this->isSameCard(
                    firstCard: $firstCard,
                    secondCard: $candidate,
                )
            ) {
                return $candidate;
            }
        }

        throw new \RuntimeException(
            'Impossible de générer une seconde carte pour ce choix.',
        );
    }

    private function isSameCard(
        Card $firstCard,
        Card $secondCard,
    ): bool {
        $firstCardId =
            $firstCard->getId();

        $secondCardId =
            $secondCard->getId();

        if (
            $firstCardId === null
            || $secondCardId === null
        ) {
            return $firstCard === $secondCard;
        }

        return $firstCardId
            === $secondCardId;
    }

    /**
     * Vérifie la compatibilité des deux
     * propositions.
     *
     * Cette règle ne concerne pour l'instant
     * que les équipements.
     */
    private function areCardsCompatible(
        Card $firstCard,
        Card $secondCard,
    ): bool {
        /*
         * Si l'une des cartes n'est pas
         * un équipement, aucun problème
         * de slot ne peut exister.
         */
        if (
            $firstCard->getKind()
            !== CardKind::EQUIPMENT
            || $secondCard->getKind()
            !== CardKind::EQUIPMENT
        ) {
            return true;
        }

        $firstSlot =
            $firstCard->getEquipmentSlot();

        $secondSlot =
            $secondCard->getEquipmentSlot();

        /*
         * Normalement un EQUIPMENT sans slot
         * ne devrait jamais exister grâce
         * au CardEffectConfigValidator.
         *
         * On reste toutefois tolérant ici
         * afin que le générateur ne plante
         * pas sur d'anciennes données.
         */
        if (
            $firstSlot === null
            || $secondSlot === null
        ) {
            return true;
        }

        /*
         * Deux équipements du même emplacement
         * ne sont pas privilégiés dans
         * le même choix.
         */
        return $firstSlot
            !== $secondSlot;
    }
}
