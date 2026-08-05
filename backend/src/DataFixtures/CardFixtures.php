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
                'code' => 'engine_turbo',
                'name' => 'Turbo moteur',
                'description' => 'Augmente la Vitesse de la voiture.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'effectConfig' => [
                    'kind' => 'stat_bonus',
                    'stat' => 'speed',
                    'tiers' => [
                        '1' => [
                            'value' => 2,
                        ],
                        '2' => [
                            'value' => 4,
                        ],
                        '3' => [
                            'value' => 7,
                        ],
                    ],
                ],
            ],
            [
                'code' => 'semi_slick_tires',
                'name' => 'Pneus semi-slick',
                'description' => 'Augmente le Grip de la voiture.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'effectConfig' => [
                    'kind' => 'stat_bonus',
                    'stat' => 'grip',
                    'tiers' => [
                        '1' => [
                            'value' => 3,
                        ],
                        '2' => [
                            'value' => 5,
                        ],
                        '3' => [
                            'value' => 8,
                        ],
                    ],
                ],
            ],
            [
                'code' => 'turbo',
                'name' => 'Turbo',
                'description' => 'Accorde un bonus de Vitesse sur une ligne droite.',
                'rarity' => 'RARE',
                'type' => 'ACTIVE',
                'effectConfig' => [
                    'kind' => 'event_stat_bonus',
                    'event' => 'straight',
                    'stat' => 'speed',
                    'tiers' => [
                        '1' => [
                            'value' => 8,
                            'maxActivations' => 1,
                        ],
                        '2' => [
                            'value' => 11,
                            'maxActivations' => 1,
                        ],
                        '3' => [
                            'value' => 14,
                            'maxActivations' => 2,
                        ],
                    ],
                ],
            ],
            [
                'code' => 'drift',
                'name' => 'Drift',
                'description' => 'Accorde un bonus de Grip dans les virages.',
                'rarity' => 'RARE',
                'type' => 'ACTIVE',
                'effectConfig' => [
                    'kind' => 'event_stat_bonus',
                    'event' => 'turn',
                    'stat' => 'grip',
                    'tiers' => [
                        '1' => [
                            'value' => 8,
                            'maxActivations' => 1,
                        ],
                        '2' => [
                            'value' => 11,
                            'maxActivations' => 1,
                        ],
                        '3' => [
                            'value' => 15,
                            'maxActivations' => 2,
                        ],
                    ],
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
