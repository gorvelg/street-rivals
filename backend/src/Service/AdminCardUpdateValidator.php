<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class AdminCardUpdateValidator
{
    private const ALLOWED_FIELDS = [
        'name',
        'type',
        'rarity',
        'effectConfig',
    ];

    private const MAX_NAME_LENGTH = 120;
    private const MAX_TYPE_LENGTH = 60;
    private const MAX_RARITY_LENGTH = 60;

    private const MAX_JSON_DEPTH = 8;
    private const MAX_JSON_SIZE = 50_000;
    private const MAX_STRING_LENGTH = 1_000;
    private const MAX_ARRAY_ITEMS = 200;

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *     name?: string,
     *     type?: string,
     *     rarity?: string,
     *     effectConfig?: array<mixed>
     * }
     */
    public function validate(
        array $payload,
    ): array {
        if ($payload === []) {
            throw new UnprocessableEntityHttpException(
                'Aucune donnée de modification reçue.',
            );
        }

        $unknownFields = array_diff(
            array_keys($payload),
            self::ALLOWED_FIELDS,
        );

        if ($unknownFields !== []) {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    'Champ(s) non autorisé(s) : %s.',
                    implode(', ', $unknownFields),
                ),
            );
        }

        $normalizedPayload = [];

        if (array_key_exists('name', $payload)) {
            $normalizedPayload['name'] =
                $this->validateString(
                    value: $payload['name'],
                    label: 'Le nom',
                    maximumLength:
                    self::MAX_NAME_LENGTH,
                );
        }

        if (array_key_exists('type', $payload)) {
            $normalizedPayload['type'] =
                $this->validateString(
                    value: $payload['type'],
                    label: 'Le type',
                    maximumLength:
                    self::MAX_TYPE_LENGTH,
                );
        }

        if (array_key_exists('rarity', $payload)) {
            $normalizedPayload['rarity'] =
                $this->validateString(
                    value: $payload['rarity'],
                    label: 'La rareté',
                    maximumLength:
                    self::MAX_RARITY_LENGTH,
                );
        }

        if (
            array_key_exists(
                'effectConfig',
                $payload,
            )
        ) {
            $effectConfig =
                $payload['effectConfig'];

            if (!is_array($effectConfig)) {
                throw new UnprocessableEntityHttpException(
                    'La configuration des effets doit être un objet JSON.',
                );
            }

            $this->validateJsonValue(
                value: $effectConfig,
                path: 'effectConfig',
                depth: 0,
            );

            $encodedConfiguration = json_encode(
                $effectConfig,
                JSON_THROW_ON_ERROR,
            );

            if (
                strlen($encodedConfiguration)
                > self::MAX_JSON_SIZE
            ) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'La configuration des effets dépasse la taille maximale de %d octets.',
                        self::MAX_JSON_SIZE,
                    ),
                );
            }

            $normalizedPayload['effectConfig'] =
                $effectConfig;
        }

        return $normalizedPayload;
    }

    private function validateString(
        mixed $value,
        string $label,
        int $maximumLength,
    ): string {
        if (!is_string($value)) {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    '%s doit être une chaîne de caractères.',
                    $label,
                ),
            );
        }

        $normalizedValue = trim($value);

        if ($normalizedValue === '') {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    '%s ne peut pas être vide.',
                    $label,
                ),
            );
        }

        if (
            mb_strlen($normalizedValue)
            > $maximumLength
        ) {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    '%s ne peut pas dépasser %d caractères.',
                    $label,
                    $maximumLength,
                ),
            );
        }

        return $normalizedValue;
    }

    private function validateJsonValue(
        mixed $value,
        string $path,
        int $depth,
    ): void {
        if ($depth > self::MAX_JSON_DEPTH) {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    'La profondeur maximale est dépassée dans %s.',
                    $path,
                ),
            );
        }

        if (is_array($value)) {
            if (
                count($value)
                > self::MAX_ARRAY_ITEMS
            ) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        '%s contient trop d’éléments.',
                        $path,
                    ),
                );
            }

            foreach ($value as $key => $childValue) {
                if (
                    is_string($key)
                    && trim($key) === ''
                ) {
                    throw new UnprocessableEntityHttpException(
                        sprintf(
                            'Une clé vide a été trouvée dans %s.',
                            $path,
                        ),
                    );
                }

                $childPath = sprintf(
                    '%s.%s',
                    $path,
                    (string) $key,
                );

                $this->validateJsonValue(
                    value: $childValue,
                    path: $childPath,
                    depth: $depth + 1,
                );
            }

            return;
        }

        if (is_string($value)) {
            if (
                mb_strlen($value)
                > self::MAX_STRING_LENGTH
            ) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'La valeur %s est trop longue.',
                        $path,
                    ),
                );
            }

            return;
        }

        if (
            is_int($value)
            || is_bool($value)
            || $value === null
        ) {
            return;
        }

        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'La valeur numérique %s est invalide.',
                        $path,
                    ),
                );
            }

            return;
        }

        throw new UnprocessableEntityHttpException(
            sprintf(
                'Le type de valeur utilisé dans %s n’est pas autorisé.',
                $path,
            ),
        );
    }
}
