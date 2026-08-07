<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\GameSetting;
use App\Entity\User;
use App\Enum\GameEventType;
use App\Enum\GameSettingKey;
use App\Repository\GameSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class GameSettingManager
{
    public function __construct(
        private readonly GameSettingRepository $repository,
        private readonly GameSettingDefinitionRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
        private readonly GameEventTracker $eventTracker,
    ) {
    }

    public function getInt(
        GameSettingKey $key,
    ): int {
        $setting = $this->repository
            ->findOneByKey($key->value);

        if ($setting instanceof GameSetting) {
            $value = $setting->getValue();

            if (is_int($value)) {
                return $value;
            }
        }

        $definition = $this->registry
            ->getDefinition($key);

        return $definition['default'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAllForAdmin(): array
    {
        $keys = array_map(
            static fn (
                GameSettingKey $key,
            ): string => $key->value,
            GameSettingKey::cases(),
        );

        $storedSettings = $this->repository
            ->findIndexedByKeys($keys);

        $result = [];

        foreach (
            GameSettingKey::cases()
            as $settingKey
        ) {
            $definition = $this->registry
                ->getDefinition($settingKey);

            $storedSetting =
                $storedSettings[
                $settingKey->value
                ] ?? null;

            $result[] = [
                'key' => $settingKey->value,

                'label' =>
                    $definition['label'],

                'description' =>
                    $definition['description'],

                'type' =>
                    $definition['type'],

                'value' =>
                    $storedSetting?->getValue()
                    ?? $definition['default'],

                'defaultValue' =>
                    $definition['default'],

                'minimum' =>
                    $definition['minimum'],

                'maximum' =>
                    $definition['maximum'],

                'unit' =>
                    $definition['unit'],

                'isOverridden' =>
                    $storedSetting
                    instanceof GameSetting,

                'updatedAt' =>
                    $storedSetting
                        ?->getUpdatedAt()
                        ->format(
                            \DateTimeInterface::ATOM,
                        ),
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array{
     *     updated: bool,
     *     changes: array<string, array{
     *         before: int,
     *         after: int
     *     }>,
     *     settings: list<array<string, mixed>>
     * }
     */
    public function updateMany(
        array $values,
        User $administrator,
    ): array {
        if ($values === []) {
            throw new UnprocessableEntityHttpException(
                'Aucun paramètre n’a été envoyé.',
            );
        }

        $validatedValues = [];

        foreach ($values as $rawKey => $value) {
            $settingKey =
                GameSettingKey::tryFrom(
                    $rawKey,
                );

            if (
                !$settingKey
                    instanceof GameSettingKey
            ) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'Le paramètre "%s" est inconnu.',
                        $rawKey,
                    ),
                );
            }

            if (!is_int($value)) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'Le paramètre "%s" doit être un nombre entier.',
                        $rawKey,
                    ),
                );
            }

            $definition = $this->registry
                ->getDefinition($settingKey);

            if (
                $value
                < $definition['minimum']
                || $value
                > $definition['maximum']
            ) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'Le paramètre "%s" doit être compris entre %d et %d.',
                        $rawKey,
                        $definition['minimum'],
                        $definition['maximum'],
                    ),
                );
            }

            $validatedValues[
            $settingKey->value
            ] = $value;
        }

        return $this->entityManager
            ->wrapInTransaction(
                function () use (
                    $validatedValues,
                    $administrator,
                ): array {
                    $changes = [];

                    foreach (
                        $validatedValues
                        as $key => $newValue
                    ) {
                        $settingKey =
                            GameSettingKey::from(
                                $key,
                            );

                        $oldValue = $this->getInt(
                            $settingKey,
                        );

                        if ($oldValue === $newValue) {
                            continue;
                        }

                        $setting = $this->repository
                            ->findOneByKey($key);

                        if (
                            !$setting
                                instanceof GameSetting
                        ) {
                            $setting = new GameSetting(
                                key: $key,
                                value: $newValue,
                            );

                            $this->entityManager
                                ->persist($setting);
                        } else {
                            $setting->setValue(
                                $newValue,
                            );
                        }

                        $changes[$key] = [
                            'before' => $oldValue,
                            'after' => $newValue,
                        ];
                    }

                    if ($changes !== []) {
                        $this->eventTracker->track(
                            type:
                            GameEventType::
                            ADMIN_GAME_SETTINGS_UPDATED,

                            user: $administrator,

                            payload: [
                                'administratorId' =>
                                    $administrator->getId(),

                                'administratorEmail' =>
                                    $administrator->getEmail(),

                                'changes' => $changes,
                            ],
                        );

                        $this->entityManager->flush();
                    }

                    return [
                        'updated' => $changes !== [],
                        'changes' => $changes,
                        'settings' =>
                            $this->getAllForAdmin(),
                    ];
                },
            );
    }
}
