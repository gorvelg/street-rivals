<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\GameSettingKey;

final class GameSettingDefinitionRegistry
{
    /**
     * @return array{
     *     label: string,
     *     description: string,
     *     type: string,
     *     default: int,
     *     minimum: int,
     *     maximum: int,
     *     unit: string
     * }
     */
    public function getDefinition(
        GameSettingKey $key,
    ): array {
        return match ($key) {
            GameSettingKey::DUEL_DAILY_ATTACK_LIMIT => [
                'label' =>
                    'Limite quotidienne par attaquant',

                'description' =>
                    'Nombre maximal de duels qu’une voiture peut lancer sur une journée.',

                'type' => 'integer',
                'default' => 20,
                'minimum' => 1,
                'maximum' => 500,
                'unit' => 'duels',
            ],

            GameSettingKey::DUEL_PAIR_DAILY_LIMIT => [
                'label' =>
                    'Limite quotidienne par paire',

                'description' =>
                    'Nombre maximal de duels autorisés chaque jour entre les deux mêmes voitures.',

                'type' => 'integer',
                'default' => 3,
                'minimum' => 1,
                'maximum' => 100,
                'unit' => 'duels',
            ],

            GameSettingKey::DUEL_PAIR_COOLDOWN_SECONDS => [
                'label' =>
                    'Cooldown entre deux voitures',

                'description' =>
                    'Délai minimal entre deux duels impliquant la même paire de voitures.',

                'type' => 'integer',
                'default' => 600,
                'minimum' => 0,
                'maximum' => 86400,
                'unit' => 'secondes',
            ],
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAllDefinitions(): array
    {
        $definitions = [];

        foreach (
            GameSettingKey::cases()
            as $settingKey
        ) {
            $definitions[
            $settingKey->value
            ] = $this->getDefinition(
                $settingKey,
            );
        }

        return $definitions;
    }
}
