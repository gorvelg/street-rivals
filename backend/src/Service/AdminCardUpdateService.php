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
        $validatedPayload =
            $this->validator->validate($payload);

        $before = $this->serializeCard($card);

        return $this->entityManager
            ->wrapInTransaction(
                function () use (
                    $card,
                    $administrator,
                    $validatedPayload,
                    $before,
                ): array {
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

                    $after = $this->serializeCard(
                        $card,
                    );

                    $changes = $this->buildChanges(
                        before: $before,
                        after: $after,
                    );

                    if ($changes === []) {
                        return [
                            'updated' => false,
                            'card' => $after,
                            'changes' => [],
                        ];
                    }

                    $this->eventTracker->track(
                        type:
                        GameEventType::
                        ADMIN_CARD_UPDATED,

                        user: $administrator,

                        payload: [
                            'administratorId' =>
                                $administrator->getId(),

                            'administratorEmail' =>
                                $administrator->getEmail(),

                            'cardId' => $card->getId(),

                            'cardCode' =>
                                $card->getCode(),

                            'changes' => $changes,
                        ],
                    );

                    $this->entityManager->flush();

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
            'id' => $card->getId(),
            'code' => $card->getCode(),
            'name' => $card->getName(),
            'type' => $card->getType(),
            'rarity' => $card->getRarity(),

            'effectConfig' =>
                $card->getEffectConfig() ?? [],
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
            ] as $field
        ) {
            if (
                $before[$field]
                === $after[$field]
            ) {
                continue;
            }

            $changes[$field] = [
                'before' => $before[$field],
                'after' => $after[$field],
            ];
        }

        return $changes;
    }
}
