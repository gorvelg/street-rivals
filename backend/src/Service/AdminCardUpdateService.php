<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Card;
use App\Entity\User;
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
         * Première validation :
         * structure générale du payload envoyé
         * par l'administration.
         */
        $validatedPayload =
            $this->validator->validate(
                $payload,
            );

        /*
         * État de la carte avant modification.
         * Il servira à générer l'historique
         * des changements.
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
                     * Nom
                     */
                    if (
                        array_key_exists(
                            'name',
                            $validatedPayload,
                        )
                    ) {
                        $card->setName(
                            $validatedPayload[
                            'name'
                            ],
                        );
                    }

                    /*
                     * Type historique de la carte.
                     *
                     * On conserve ce champ pour
                     * l'instant car il est déjà
                     * utilisé par le gameplay.
                     */
                    if (
                        array_key_exists(
                            'type',
                            $validatedPayload,
                        )
                    ) {
                        $card->setType(
                            $validatedPayload[
                            'type'
                            ],
                        );
                    }

                    /*
                     * Rareté
                     */
                    if (
                        array_key_exists(
                            'rarity',
                            $validatedPayload,
                        )
                    ) {
                        $card->setRarity(
                            $validatedPayload[
                            'rarity'
                            ],
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
                     * IMPORTANT
                     *
                     * À ce stade toutes les
                     * modifications ont été appliquées
                     * à l'entité mais rien n'a encore
                     * été enregistré en base.
                     *
                     * On valide donc maintenant la
                     * cohérence complète de la carte.
                     *
                     * ABILITY :
                     *   l'effectConfig historique
                     *   reste accepté.
                     *
                     * EQUIPMENT :
                     *   equipmentSlot obligatoire
                     *   + validation des tiers/stats.
                     *
                     * STAT_BOOST :
                     *   validation des tiers/stats.
                     */
                    $this
                        ->cardEffectConfigValidator
                        ->validate(
                            $card,
                        );

                    /*
                     * Nouvel état après modification
                     * et validation.
                     */
                    $after = $this->serializeCard(
                        $card,
                    );

                    /*
                     * Détermination précise des
                     * propriétés qui ont changé.
                     */
                    $changes = $this->buildChanges(
                        before: $before,
                        after: $after,
                    );

                    /*
                     * Rien n'a réellement changé.
                     *
                     * Aucun événement n'est créé
                     * et aucun flush n'est nécessaire.
                     */
                    if ($changes === []) {
                        return [
                            'updated' => false,

                            'card' => $after,

                            'changes' => [],
                        ];
                    }

                    /*
                     * Historisation de la modification
                     * administrative.
                     */
                    $this->eventTracker->track(
                        type:
                        GameEventType::
                        ADMIN_CARD_UPDATED,

                        user: $administrator,

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
                     * Enregistrement de la carte
                     * et de l'événement.
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

            'type' =>
                $card->getType(),

            'rarity' =>
                $card->getRarity(),

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
