<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Card;
use App\Entity\User;
use App\Enum\CardKind;
use App\Enum\EquipmentSlot;
use App\Enum\GameEventType;
use Doctrine\ORM\EntityManagerInterface;

final class AdminCardUpdateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminCardUpdateValidator $validator,
        private readonly CardEffectConfigValidator $cardEffectConfigValidator,
        private readonly GameEventTracker $eventTracker,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function update(
        Card $card,
        User $administrator,
        array $payload,
    ): array {
        /*
         * Validation de la structure du PATCH.
         */
        $validatedPayload =
            $this->validator->validate(
                $payload,
            );

        /*
         * État avant modification.
         */
        $before = $this->serializeCard(
            $card,
        );

        return $this->entityManager
            ->wrapInTransaction(
                function () use (
                    $card,
                    $administrator,
                    $validatedPayload,
                    $before,
                ): array {
                    /*
                     * Nom.
                     */
                    if (
                        array_key_exists(
                            'name',
                            $validatedPayload,
                        )
                    ) {
                        $card->setName(
                            $validatedPayload['name'],
                        );
                    }

                    /*
                     * Type historique :
                     *
                     * PASSIVE / ACTIVE
                     */
                    if (
                        array_key_exists(
                            'type',
                            $validatedPayload,
                        )
                    ) {
                        $card->setType(
                            $validatedPayload['type'],
                        );
                    }

                    /*
                     * Rareté.
                     */
                    if (
                        array_key_exists(
                            'rarity',
                            $validatedPayload,
                        )
                    ) {
                        $card->setRarity(
                            $validatedPayload['rarity'],
                        );
                    }

                    /*
                     * Nouvelle famille :
                     *
                     * ability
                     * equipment
                     * stat_boost
                     *
                     * IMPORTANT :
                     * on applique kind AVANT equipmentSlot.
                     */
                    if (
                        array_key_exists(
                            'kind',
                            $validatedPayload,
                        )
                    ) {
                        $card->setKind(
                            CardKind::from(
                                $validatedPayload['kind'],
                            ),
                        );
                    }

                    /*
                     * Palier maximal.
                     */
                    if (
                        array_key_exists(
                            'maxTier',
                            $validatedPayload,
                        )
                    ) {
                        $card->setMaxTier(
                            $validatedPayload['maxTier'],
                        );
                    }

                    /*
                     * Emplacement d'équipement.
                     */
                    if (
                        array_key_exists(
                            'equipmentSlot',
                            $validatedPayload,
                        )
                    ) {
                        $equipmentSlot =
                            $validatedPayload[
                            'equipmentSlot'
                            ];

                        $card->setEquipmentSlot(
                            $equipmentSlot !== null
                                ? EquipmentSlot::from(
                                $equipmentSlot,
                            )
                                : null,
                        );
                    }

                    /*
                     * Configuration des effets.
                     */
                    if (
                        array_key_exists(
                            'effectConfig',
                            $validatedPayload,
                        )
                    ) {
                        $card->setEffectConfig(
                            $validatedPayload[
                            'effectConfig'
                            ],
                        );
                    }

                    /*
                     * Validation de l'état FINAL
                     * de la carte.
                     *
                     * Cela vérifie notamment :
                     *
                     * equipment
                     * -> equipmentSlot obligatoire
                     *
                     * stat_boost
                     * -> tiers/statistiques valides
                     *
                     * equipment
                     * -> tiers/statistiques valides
                     */
                    $this
                        ->cardEffectConfigValidator
                        ->validate(
                            $card,
                        );

                    /*
                     * État après modification.
                     */
                    $after = $this->serializeCard(
                        $card,
                    );

                    $changes = $this->buildChanges(
                        before: $before,
                        after: $after,
                    );

                    /*
                     * Aucun changement réel.
                     */
                    if ($changes === []) {
                        return [
                            'updated' => false,
                            'card' => $after,
                            'changes' => [],
                        ];
                    }

                    /*
                     * Événement analytique/admin.
                     */
                    $this->eventTracker->track(
                        type:
                        GameEventType::
                        ADMIN_CARD_UPDATED,

                        user:
                        $administrator,

                        payload: [
                            'administratorId' =>
                                $administrator
                                    ->getId(),

                            'administratorEmail' =>
                                $administrator
                                    ->getEmail(),

                            'cardId' =>
                                $card->getId(),

                            'cardCode' =>
                                $card->getCode(),

                            'changes' =>
                                $changes,
                        ],
                    );

                    /*
                     * Sauvegarde de la carte
                     * + événement.
                     */
                    $this->entityManager
                        ->flush();

                    return [
                        'updated' => true,
                        'card' => $after,
                        'changes' => $changes,
                    ];
                },
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCard(
        Card $card,
    ): array {
        return [
            'id' =>
                $card->getId(),

            'code' =>
                $card->getCode(),

            'name' =>
                $card->getName(),

            /*
             * PASSIVE / ACTIVE
             */
            'type' =>
                $card->getType(),

            'rarity' =>
                $card->getRarity(),

            /*
             * ability / equipment / stat_boost
             */
            'kind' =>
                $card->getKind()->value,

            /*
             * engine / wheels / ...
             */
            'equipmentSlot' =>
                $card
                    ->getEquipmentSlot()
                    ?->value,

            'maxTier' =>
                $card->getMaxTier(),

            'effectConfig' =>
                $card->getEffectConfig()
                ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @return array<string, array{
     *     before: mixed,
     *     after: mixed
     * }>
     */
    private function buildChanges(
        array $before,
        array $after,
    ): array {
        $changes = [];

        foreach (
            [
                'name',
                'type',
                'rarity',
                'kind',
                'equipmentSlot',
                'maxTier',
                'effectConfig',
            ]
            as $field
        ) {
            if (
                $before[$field]
                === $after[$field]
            ) {
                continue;
            }

            $changes[$field] = [
                'before' =>
                    $before[$field],

                'after' =>
                    $after[$field],
            ];
        }

        return $changes;
    }
}
