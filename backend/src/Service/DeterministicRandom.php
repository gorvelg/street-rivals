<?php

declare(strict_types=1);

namespace App\Service;

final class DeterministicRandom
{
    public function integer(
        string $seed,
        string $scope,
        int $minimum,
        int $maximum,
    ): int {
        if ($minimum > $maximum) {
            throw new \InvalidArgumentException(
                'La valeur minimale ne peut pas dépasser la valeur maximale.'
            );
        }

        $hash = hash(
            'sha256',
            sprintf('%s|%s', $seed, $scope)
        );

        /*
         * On utilise les huit premiers caractères du hash.
         * Cela donne une valeur déterministe pour un seed et un scope donnés.
         */
        $number = (int) hexdec(substr($hash, 0, 8));

        $range = ($maximum - $minimum) + 1;

        return $minimum + ($number % $range);
    }
}
