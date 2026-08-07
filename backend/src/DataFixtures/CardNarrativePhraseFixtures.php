<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\CardNarrativePhrase;
use App\Enum\NarrativeContext;
use App\Enum\NarrativePhraseType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class CardNarrativePhraseFixtures extends Fixture
{
    public function load(
        ObjectManager $manager,
    ): void {
        $this->createTurboPhrases(
            $manager,
        );

        $this->createDriftPhrases(
            $manager,
        );

        $manager->flush();
    }

    private function createTurboPhrases(
        ObjectManager $manager,
    ): void {
        $card =
            $manager
                ->getRepository(Card::class)
                ->findOneBy([
                    'code' => 'turbo',
                ]);

        if (!$card instanceof Card) {
            return;
        }

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::ACTIVATION,
            NarrativeContext::STRAIGHT,
            '{pilot} déclenche le Turbo. Le moteur hurle !',
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::ACTIVATION,
            NarrativeContext::STRAIGHT,
            'Le Turbo de {pilot} se met à siffler furieusement !',
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::ACTIVATION,
            NarrativeContext::STRAIGHT,
            '{pilot} libère toute la pression du Turbo !',
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::EFFECT,
            NarrativeContext::STRAIGHT,
            'La voiture bondit vers l’avant : {value} {stat} !',
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::EFFECT,
            NarrativeContext::STRAIGHT,
            '{pilot} gagne brutalement en vitesse : {value} !',
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::REACTION,
            NarrativeContext::STRAIGHT,
            '{opponent} tente de rester dans l’aspiration.',
            1,
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::REACTION,
            NarrativeContext::STRAIGHT,
            '{opponent} voit son adversaire s’échapper.',
            1,
        );
    }

    private function createDriftPhrases(
        ObjectManager $manager,
    ): void {
        $card =
            $manager
                ->getRepository(Card::class)
                ->findOneBy([
                    'code' => 'drift',
                ]);

        if (!$card instanceof Card) {
            return;
        }

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::ACTIVATION,
            NarrativeContext::TURN,
            '{pilot} jette la voiture en travers !',
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::ACTIVATION,
            NarrativeContext::TURN,
            '{pilot} décroche l’arrière à l’entrée du virage !',
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::ACTIVATION,
            NarrativeContext::TURN,
            '{pilot} déclenche un Drift parfaitement contrôlé !',
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::EFFECT,
            NarrativeContext::TURN,
            'La voiture glisse jusqu’au point de corde : {value} {stat} !',
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::EFFECT,
            NarrativeContext::TURN,
            '{pilot} conserve toute sa vitesse dans la courbe : {value} {stat} !',
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::REACTION,
            NarrativeContext::TURN,
            '{opponent} doit lever le pied.',
        );

        $this->add(
            $manager,
            $card,
            NarrativePhraseType::REACTION,
            NarrativeContext::TURN,
            '{opponent} voit la voiture passer en glisse devant lui.',
        );
    }

    private function add(
        ObjectManager $manager,
        Card $card,
        NarrativePhraseType $type,
        NarrativeContext $context,
        string $text,
        int $weight = 1,
    ): void {
        $phrase =
            new CardNarrativePhrase();

        $phrase
            ->setCard($card)
            ->setType($type)
            ->setContext($context)
            ->setText($text)
            ->setWeight($weight)
            ->setEnabled(true);

        $manager->persist($phrase);
    }
}
