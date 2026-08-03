<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CarRepository;
use App\State\CarProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CarRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            security: "object.getUser() == user",
            securityMessage: 'Cette voiture ne vous appartient pas.'
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            processor: CarProcessor::class
        ),
        new Patch(
            security: "object.getUser() == user",
            securityMessage: 'Cette voiture ne vous appartient pas.',
            processor: CarProcessor::class
        ),
    ],
    normalizationContext: [
        'groups' => ['car:read'],
    ],
    denormalizationContext: [
        'groups' => ['car:write'],
    ],
)]
class Car
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['car:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cars')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'pilot_name', length: 32)]
    #[Groups(['car:read', 'car:write'])]
    #[Assert\NotBlank(message: 'Le nom du pilote est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 32,
        minMessage: 'Le nom du pilote doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom du pilote ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $pilotName = null;

    #[ORM\Column(length: 7)]
    #[Groups(['car:read', 'car:write'])]
    #[Assert\NotBlank(message: 'La couleur est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'La couleur doit être au format hexadécimal, par exemple #FF0000.'
    )]
    private ?string $color = null;

    #[ORM\Column]
    #[Groups(['car:read'])]
    private int $money = 0;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(['car:read'])]
    private int $speed = 10;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(['car:read'])]
    private int $acceleration = 10;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(['car:read'])]
    private int $grip = 10;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(['car:read'])]
    private int $solidity = 10;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(['car:read'])]
    private int $level = 1;

    #[ORM\Column]
    #[Groups(['car:read'])]
    private int $xp = 0;

    #[ORM\Column(name: 'created_at')]
    #[Groups(['car:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at')]
    #[Groups(['car:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPilotName(): ?string
    {
        return $this->pilotName;
    }

    public function setPilotName(string $pilotName): static
    {
        $this->pilotName = trim($pilotName);

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = strtoupper($color);

        return $this;
    }

    public function getMoney(): int
    {
        return $this->money;
    }

    public function setMoney(int $money): static
    {
        $this->money = $money;

        return $this;
    }

    public function getSpeed(): int
    {
        return $this->speed;
    }

    public function setSpeed(int $speed): static
    {
        $this->speed = $speed;

        return $this;
    }

    public function getAcceleration(): int
    {
        return $this->acceleration;
    }

    public function setAcceleration(int $acceleration): static
    {
        $this->acceleration = $acceleration;

        return $this;
    }

    public function getGrip(): int
    {
        return $this->grip;
    }

    public function setGrip(int $grip): static
    {
        $this->grip = $grip;

        return $this;
    }

    public function getSolidity(): int
    {
        return $this->solidity;
    }

    public function setSolidity(int $solidity): static
    {
        $this->solidity = $solidity;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getXp(): int
    {
        return $this->xp;
    }

    public function setXp(int $xp): static
    {
        $this->xp = $xp;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
