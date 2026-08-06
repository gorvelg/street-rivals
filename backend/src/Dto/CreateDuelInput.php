<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateDuelInput
{
    #[Assert\NotNull(
        message: 'La voiture attaquante est obligatoire.'
    )]
    #[Assert\Positive(
        message: 'L’identifiant de la voiture attaquante est invalide.'
    )]
    public ?int $attackerCarId = null;

    #[Assert\NotNull(
        message: 'La voiture défensive est obligatoire.'
    )]
    #[Assert\Positive(
        message: 'L’identifiant de la voiture défensive est invalide.'
    )]
    public ?int $defenderCarId = null;
}
