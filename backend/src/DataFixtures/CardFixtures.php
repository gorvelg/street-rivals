<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Card;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class CardFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cards = [
            [
                'code' => 'turbo_stage_1',
                'name' => 'Turbo Stage 1',
                'description' => '+2 Vitesse.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'effectConfig' => [
                    'kind' => 'stat_bonus',
                    'stat' => 'speed',
                    'value' => 2,
                ],
            ],
            [
                'code' => 'semi_slick_tires',
                'name' => 'Pneus semi-slick',
                'description' => '+3 Grip.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'effectConfig' => [
                    'kind' => 'stat_bonus',
                    'stat' => 'grip',
                    'value' => 3,
                ],
            ],
            [
                'code' => 'turbo',
                'name' => 'Turbo',
                'description' => 'Accorde un bonus de vitesse sur une ligne droite.',
                'rarity' => 'RARE',
                'type' => 'ACTIVE',
                'effectConfig' => [
                    'kind' => 'event_stat_bonus',
                    'event' => 'straight',
                    'stat' => 'speed',
                    'value' => 8,
                    'maxActivations' => 1,
                ],
            ],
            [
                'code' => 'drift',
                'name' => 'Drift',
                'description' => 'Accorde un bonus de Grip dans un virage.',
                'rarity' => 'RARE',
                'type' => 'ACTIVE',
                'effectConfig' => [
                    'kind' => 'event_stat_bonus',
                    'event' => 'turn',
                    'stat' => 'grip',
                    'value' => 8,
                    'maxActivations' => 1,
                ],
            ],
        ];

        $repository = $manager->getRepository(Card::class);

        foreach ($cards as $data) {
            $card = $repository->findOneBy([
                'code' => $data['code'],
            ]);

            if (!$card instanceof Card) {
                $card = new Card();
                $card->setCode($data['code']);
            }

            $card
                ->setName($data['name'])
                ->setDescription($data['description'])
                ->setRarity($data['rarity'])
                ->setType($data['type'])
                ->setEffectConfig($data['effectConfig'])
                ->setEnabled(true);

            $card->touch();

            $manager->persist($card);
        }

        $manager->flush();
    }
}
