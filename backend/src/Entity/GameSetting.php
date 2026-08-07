<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GameSettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(
    repositoryClass: GameSettingRepository::class
)]
#[ORM\Table(name: 'game_setting')]
#[ORM\UniqueConstraint(
    name: 'uniq_game_setting_key',
    columns: ['setting_key']
)]
class GameSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(
        name: 'setting_key',
        length: 120,
        unique: true,
    )]
    private string $key;

    #[ORM\Column(type: Types::JSON)]
    private mixed $value;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $key,
        mixed $value,
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->updatedAt =
            new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(
        mixed $value,
    ): self {
        $this->value = $value;
        $this->updatedAt =
            new \DateTimeImmutable('now');

        return $this;
    }

    public function getUpdatedAt():
    \DateTimeImmutable {
        return $this->updatedAt;
    }
}
