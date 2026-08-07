<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Card;
use App\Enum\CardKind;
use App\Enum\EquipmentSlot;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class CardFixtures extends Fixture
{
    public function load(
        ObjectManager $manager,
    ): void {
        $cards = [
            /*
             * =====================================================
             * CAPACITÉS
             * =====================================================
             */

            [
                'code' => 'turbo',
                'name' => 'Turbo',
                'description' =>
                    'Déclenche une forte poussée de vitesse en ligne droite.',
                'rarity' => 'RARE',
                'type' => 'ACTIVE',
                'kind' => CardKind::ABILITY,
                'equipmentSlot' => null,
                'maxTier' => 3,
                'effectConfig' => [
                    'kind' =>
                        'event_stat_bonus',

                    'event' =>
                        'straight',

                    'stat' =>
                        'speed',

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
                'description' =>
                    'Améliore fortement l’adhérence dans les virages.',
                'rarity' => 'RARE',
                'type' => 'ACTIVE',
                'kind' => CardKind::ABILITY,
                'equipmentSlot' => null,
                'maxTier' => 3,
                'effectConfig' => [
                    'kind' =>
                        'event_stat_bonus',

                    'event' =>
                        'turn',

                    'stat' =>
                        'grip',

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

            /*
             * =====================================================
             * ÉQUIPEMENTS — MOTEUR
             * =====================================================
             */

            [
                'code' => 'sport_intake',
                'name' => 'Admission sport',
                'description' =>
                    'Améliore la réponse du moteur et les accélérations.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::ENGINE,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'acceleration' => 2,
                        ],
                        '2' => [
                            'acceleration' => 4,
                        ],
                        '3' => [
                            'acceleration' => 6,
                        ],
                    ],
                ],
            ],

            [
                'code' => 'competition_turbo',
                'name' => 'Turbo compétition',
                'description' =>
                    'Augmente fortement la vitesse au prix d’une mécanique plus fragile.',
                'rarity' => 'RARE',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::ENGINE,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'speed' => 3,
                            'solidity' => -1,
                        ],
                        '2' => [
                            'speed' => 5,
                            'solidity' => -1,
                        ],
                        '3' => [
                            'speed' => 7,
                            'solidity' => -2,
                        ],
                    ],
                ],
            ],

            /*
             * =====================================================
             * ÉQUIPEMENTS — ROUES
             * =====================================================
             */

            [
                'code' => 'semi_slick_tires',
                'name' => 'Pneus semi-slick',
                'description' =>
                    'Améliore fortement l’adhérence de la voiture.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::WHEELS,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'grip' => 2,
                        ],
                        '2' => [
                            'grip' => 4,
                        ],
                        '3' => [
                            'grip' => 6,
                        ],
                    ],
                ],
            ],

            [
                'code' => 'lightweight_wheels',
                'name' => 'Jantes légères',
                'description' =>
                    'Réduit les masses non suspendues pour améliorer les accélérations.',
                'rarity' => 'RARE',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::WHEELS,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'acceleration' => 2,
                            'solidity' => -1,
                        ],
                        '2' => [
                            'acceleration' => 3,
                            'solidity' => -1,
                        ],
                        '3' => [
                            'acceleration' => 5,
                            'solidity' => -2,
                        ],
                    ],
                ],
            ],

            /*
             * =====================================================
             * ÉQUIPEMENTS — FREINS
             * =====================================================
             */

            [
                'code' => 'sport_brakes',
                'name' => 'Freins sport',
                'description' =>
                    'Permet de retarder les freinages et améliore la tenue de route.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::BRAKES,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'grip' => 2,
                            'solidity' => 1,
                        ],
                        '2' => [
                            'grip' => 3,
                            'solidity' => 1,
                        ],
                        '3' => [
                            'grip' => 5,
                            'solidity' => 2,
                        ],
                    ],
                ],
            ],

            [
                'code' => 'carbon_brakes',
                'name' => 'Freins carbone',
                'description' =>
                    'Un système de freinage très léger favorisant les relances.',
                'rarity' => 'RARE',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::BRAKES,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'acceleration' => 2,
                            'solidity' => -1,
                        ],
                        '2' => [
                            'acceleration' => 4,
                            'solidity' => -1,
                        ],
                        '3' => [
                            'acceleration' => 6,
                            'solidity' => -1,
                        ],
                    ],
                ],
            ],

            /*
             * =====================================================
             * ÉQUIPEMENTS — BOÎTE DE VITESSES
             * =====================================================
             */

            [
                'code' => 'short_gearbox',
                'name' => 'Boîte courte',
                'description' =>
                    'Des rapports courts pour privilégier les accélérations.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::GEARBOX,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'acceleration' => 3,
                            'speed' => -1,
                        ],
                        '2' => [
                            'acceleration' => 5,
                            'speed' => -1,
                        ],
                        '3' => [
                            'acceleration' => 7,
                            'speed' => -2,
                        ],
                    ],
                ],
            ],

            [
                'code' => 'long_gearbox',
                'name' => 'Boîte longue',
                'description' =>
                    'Des rapports longs permettant d’atteindre une vitesse supérieure.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::GEARBOX,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'speed' => 3,
                            'acceleration' => -1,
                        ],
                        '2' => [
                            'speed' => 5,
                            'acceleration' => -1,
                        ],
                        '3' => [
                            'speed' => 7,
                            'acceleration' => -2,
                        ],
                    ],
                ],
            ],

            /*
             * =====================================================
             * ÉQUIPEMENTS — CHÂSSIS
             * =====================================================
             */

            [
                'code' => 'roll_cage',
                'name' => 'Arceau renforcé',
                'description' =>
                    'Renforce considérablement la voiture mais ajoute du poids.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::CHASSIS,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'solidity' => 3,
                            'acceleration' => -1,
                        ],
                        '2' => [
                            'solidity' => 5,
                            'acceleration' => -1,
                        ],
                        '3' => [
                            'solidity' => 7,
                            'acceleration' => -2,
                        ],
                    ],
                ],
            ],

            [
                'code' => 'lightweight_chassis',
                'name' => 'Châssis allégé',
                'description' =>
                    'Une préparation radicale privilégiant les performances à la résistance.',
                'rarity' => 'RARE',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::CHASSIS,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'speed' => 1,
                            'acceleration' => 2,
                            'solidity' => -2,
                        ],
                        '2' => [
                            'speed' => 1,
                            'acceleration' => 4,
                            'solidity' => -3,
                        ],
                        '3' => [
                            'speed' => 2,
                            'acceleration' => 6,
                            'solidity' => -4,
                        ],
                    ],
                ],
            ],

            /*
             * =====================================================
             * ÉQUIPEMENTS — AÉRODYNAMIQUE
             * =====================================================
             */

            [
                'code' => 'rear_spoiler',
                'name' => 'Aileron arrière',
                'description' =>
                    'Augmente l’appui dans les virages au détriment de la vitesse de pointe.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::AERO,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'grip' => 3,
                            'speed' => -1,
                        ],
                        '2' => [
                            'grip' => 5,
                            'speed' => -1,
                        ],
                        '3' => [
                            'grip' => 7,
                            'speed' => -2,
                        ],
                    ],
                ],
            ],

            [
                'code' => 'aero_kit',
                'name' => 'Kit aérodynamique',
                'description' =>
                    'Un ensemble aérodynamique complet améliorant vitesse et adhérence.',
                'rarity' => 'RARE',
                'type' => 'PASSIVE',
                'kind' => CardKind::EQUIPMENT,
                'equipmentSlot' =>
                    EquipmentSlot::AERO,
                'maxTier' => 3,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'speed' => 2,
                            'grip' => 2,
                            'solidity' => -1,
                        ],
                        '2' => [
                            'speed' => 4,
                            'grip' => 3,
                            'solidity' => -1,
                        ],
                        '3' => [
                            'speed' => 5,
                            'grip' => 5,
                            'solidity' => -2,
                        ],
                    ],
                ],
            ],

            /*
             * =====================================================
             * BONUS PERMANENTS
             *
             * Ils ne sont jamais "équipés".
             * Ils s'appliquent automatiquement.
             *
             * maxTier = 5 permet également de tester
             * tout le nouveau système de paliers dynamiques.
             * =====================================================
             */

            [
                'code' => 'natural_speed',
                'name' => 'Pointe de vitesse',
                'description' =>
                    'Augmente définitivement la vitesse de la voiture.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'kind' => CardKind::STAT_BOOST,
                'equipmentSlot' => null,
                'maxTier' => 5,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'speed' => 1,
                        ],
                        '2' => [
                            'speed' => 2,
                        ],
                        '3' => [
                            'speed' => 3,
                        ],
                        '4' => [
                            'speed' => 4,
                        ],
                        '5' => [
                            'speed' => 5,
                        ],
                    ],
                ],
            ],

            [
                'code' => 'launch_reflexes',
                'name' => 'Réflexes',
                'description' =>
                    'Augmente définitivement l’accélération de la voiture.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'kind' => CardKind::STAT_BOOST,
                'equipmentSlot' => null,
                'maxTier' => 5,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'acceleration' => 1,
                        ],
                        '2' => [
                            'acceleration' => 2,
                        ],
                        '3' => [
                            'acceleration' => 3,
                        ],
                        '4' => [
                            'acceleration' => 4,
                        ],
                        '5' => [
                            'acceleration' => 5,
                        ],
                    ],
                ],
            ],

            [
                'code' => 'racing_line',
                'name' => 'Maîtrise des trajectoires',
                'description' =>
                    'Augmente définitivement l’adhérence de la voiture.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'kind' => CardKind::STAT_BOOST,
                'equipmentSlot' => null,
                'maxTier' => 5,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'grip' => 1,
                        ],
                        '2' => [
                            'grip' => 2,
                        ],
                        '3' => [
                            'grip' => 3,
                        ],
                        '4' => [
                            'grip' => 4,
                        ],
                        '5' => [
                            'grip' => 5,
                        ],
                    ],
                ],
            ],

            [
                'code' => 'reinforced_body',
                'name' => 'Robustesse',
                'description' =>
                    'Augmente définitivement la solidité de la voiture.',
                'rarity' => 'COMMON',
                'type' => 'PASSIVE',
                'kind' => CardKind::STAT_BOOST,
                'equipmentSlot' => null,
                'maxTier' => 5,
                'effectConfig' => [
                    'tiers' => [
                        '1' => [
                            'solidity' => 1,
                        ],
                        '2' => [
                            'solidity' => 2,
                        ],
                        '3' => [
                            'solidity' => 3,
                        ],
                        '4' => [
                            'solidity' => 4,
                        ],
                        '5' => [
                            'solidity' => 5,
                        ],
                    ],
                ],
            ],
        ];

        $repository =
            $manager->getRepository(
                Card::class,
            );

        foreach ($cards as $data) {
            $card =
                $repository->findOneBy([
                    'code' =>
                        $data['code'],
                ]);

            if (!$card instanceof Card) {
                $card = new Card();

                $card->setCode(
                    $data['code'],
                );
            }

            $card
                ->setName(
                    $data['name'],
                )
                ->setDescription(
                    $data['description'],
                )
                ->setRarity(
                    $data['rarity'],
                )
                ->setType(
                    $data['type'],
                )
                ->setKind(
                    $data['kind'],
                )
                ->setEquipmentSlot(
                    $data['equipmentSlot'],
                )
                ->setMaxTier(
                    $data['maxTier'],
                )
                ->setEffectConfig(
                    $data['effectConfig'],
                )
                ->setEnabled(true);

            $card->touch();

            $manager->persist(
                $card,
            );
        }

        $manager->flush();
    }
}
