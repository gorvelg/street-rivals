<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class GenerateCardChoiceInput
{
    #[Assert\NotNull(message: 'L’identifiant de la voiture est obligatoire.')]
    #[Assert\Positive(message: 'L’identifiant de la voiture est invalide.')]
    public ?int $carId = null;
}
