<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\CardKind;
use App\Enum\EquipmentSlot;
use JsonException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class AdminCardUpdateValidator
{
    private const ALLOWED_FIELDS = [
        'name',
        'type',
        'rarity',
        'kind',
        'equipmentSlot',
        'maxTier',
        'effectConfig',
    ];

    private const MAX_NAME_LENGTH = 120;
    private const MAX_TYPE_LENGTH = 60;
    private const MAX_RARITY_LENGTH = 60;

    private const MIN_MAX_TIER = 1;
    private const MAX_MAX_TIER = 10;

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
     *     kind?: string,
     *     equipmentSlot?: string|null,
     *     maxTier?: int,
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

        /*
         * Refus de toute propriété que
         * l'administration n'est pas autorisée
         * à modifier.
         */
        $unknownFields = array_diff(
            array_keys($payload),
            self::ALLOWED_FIELDS,
        );

        if ($unknownFields !== []) {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    'Champ(s) non autorisé(s) : %s.',
                    implode(
                        ', ',
                        $unknownFields,
                    ),
                ),
            );
        }

        $normalizedPayload = [];

        /*
         * Nom
         */
        if (
            array_key_exists(
                'name',
                $payload,
            )
        ) {
            $normalizedPayload['name'] =
                $this->validateString(
                    value: $payload['name'],
                    label: 'Le nom',
                    maximumLength:
                    self::MAX_NAME_LENGTH,
                );
        }

        /*
         * Ancien type fonctionnel :
         *
         * PASSIVE
         * ACTIVE
         *
         * On conserve ce champ pour le moteur
         * de jeu existant.
         */
        if (
            array_key_exists(
                'type',
                $payload,
            )
        ) {
            $normalizedPayload['type'] =
                $this->validateString(
                    value: $payload['type'],
                    label: 'Le type',
                    maximumLength:
                    self::MAX_TYPE_LENGTH,
                );
        }

        /*
         * Rareté
         */
        if (
            array_key_exists(
                'rarity',
                $payload,
            )
        ) {
            $normalizedPayload['rarity'] =
                $this->validateString(
                    value: $payload['rarity'],
                    label: 'La rareté',
                    maximumLength:
                    self::MAX_RARITY_LENGTH,
                );
        }

        /*
         * Nouvelle famille fonctionnelle :
         *
         * ability
         * equipment
         * stat_boost
         */
        if (
            array_key_exists(
                'kind',
                $payload,
            )
        ) {
            $normalizedPayload['kind'] =
                $this->validateKind(
                    $payload['kind'],
                );
        }

        /*
         * Slot d'équipement.
         *
         * null est accepté car une ABILITY
         * ou un STAT_BOOST ne possède aucun slot.
         *
         * La cohérence finale avec "kind" sera
         * contrôlée après application du PATCH par
         * CardEffectConfigValidator.
         */
        if (
            array_key_exists(
                'equipmentSlot',
                $payload,
            )
        ) {
            $normalizedPayload[
            'equipmentSlot'
            ] = $this->validateEquipmentSlot(
                $payload['equipmentSlot'],
            );
        }

        /*
         * Palier maximal propre à la carte.
         */
        if (
            array_key_exists(
                'maxTier',
                $payload,
            )
        ) {
            $normalizedPayload['maxTier'] =
                $this->validateMaxTier(
                    $payload['maxTier'],
                );
        }

        /*
         * Configuration JSON des effets.
         */
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

            try {
                $encodedConfiguration =
                    json_encode(
                        $effectConfig,
                        JSON_THROW_ON_ERROR,
                    );
            } catch (JsonException $exception) {
                throw new UnprocessableEntityHttpException(
                    'La configuration des effets contient des données JSON invalides.',
                    $exception,
                );
            }

            if (
                strlen(
                    $encodedConfiguration,
                )
                > self::MAX_JSON_SIZE
            ) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'La configuration des effets dépasse la taille maximale de %d octets.',
                        self::MAX_JSON_SIZE,
                    ),
                );
            }

            $normalizedPayload[
            'effectConfig'
            ] = $effectConfig;
        }

        return $normalizedPayload;
    }

    private function validateKind(
        mixed $value,
    ): string {
        if (!is_string($value)) {
            throw new UnprocessableEntityHttpException(
                'La nature de la carte doit être une chaîne de caractères.',
            );
        }

        $normalizedValue = trim(
            $value,
        );

        $kind = CardKind::tryFrom(
            $normalizedValue,
        );

        if (!$kind instanceof CardKind) {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    'La nature "%s" est invalide. Valeurs autorisées : %s.',
                    $normalizedValue,
                    implode(
                        ', ',
                        array_map(
                            static fn (
                                CardKind $kind,
                            ): string =>
                            $kind->value,

                            CardKind::cases(),
                        ),
                    ),
                ),
            );
        }

        return $kind->value;
    }

    private function validateEquipmentSlot(
        mixed $value,
    ): ?string {
        /*
         * null signifie :
         *
         * aucun emplacement.
         */
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new UnprocessableEntityHttpException(
                'L’emplacement d’équipement doit être une chaîne de caractères ou null.',
            );
        }

        $normalizedValue = trim(
            $value,
        );

        /*
         * Une chaîne vide provenant par exemple
         * d'un <select> HTML est normalisée en null.
         */
        if ($normalizedValue === '') {
            return null;
        }

        $slot = EquipmentSlot::tryFrom(
            $normalizedValue,
        );

        if (!$slot instanceof EquipmentSlot) {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    'L’emplacement "%s" est invalide. Valeurs autorisées : %s.',
                    $normalizedValue,
                    implode(
                        ', ',
                        array_map(
                            static fn (
                                EquipmentSlot $slot,
                            ): string =>
                            $slot->value,

                            EquipmentSlot::cases(),
                        ),
                    ),
                ),
            );
        }

        return $slot->value;
    }

    private function validateMaxTier(
        mixed $value,
    ): int {
        /*
         * On exige réellement un entier.
         *
         * "3" n'est donc pas accepté :
         * le frontend doit envoyer 3.
         */
        if (!is_int($value)) {
            throw new UnprocessableEntityHttpException(
                'Le palier maximal doit être un nombre entier.',
            );
        }

        if (
            $value < self::MIN_MAX_TIER
            || $value > self::MAX_MAX_TIER
        ) {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    'Le palier maximal doit être compris entre %d et %d.',
                    self::MIN_MAX_TIER,
                    self::MAX_MAX_TIER,
                ),
            );
        }

        return $value;
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

        $normalizedValue = trim(
            $value,
        );

        if ($normalizedValue === '') {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    '%s ne peut pas être vide.',
                    $label,
                ),
            );
        }

        if (
            mb_strlen(
                $normalizedValue,
            )
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
        if (
            $depth
            > self::MAX_JSON_DEPTH
        ) {
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

            foreach (
                $value
                as $key => $childValue
            ) {
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
