<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class SelectCardChoiceInput
{
    #[Assert\NotNull(message: 'L’identifiant de la carte est obligatoire.')]
    #[Assert\Positive(message: 'L’identifiant de la carte est invalide.')]
    public ?int $cardId = null;
}
