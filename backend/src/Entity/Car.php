<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\CarStatsOutput;
use App\Dto\MatchmakingOpponentOutput;
use App\Repository\CarRepository;
use App\State\CarProcessor;
use App\State\CarStatsProvider;
use App\State\MatchmakingOpponentProvider;
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
            uriTemplate: '/cars/{id}/stats',
            security: "is_granted('ROLE_USER')",
            output: CarStatsOutput::class,
            provider: CarStatsProvider::class,
            normalizationContext: [
                'groups' => ['car-stats:read'],
            ],
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
        new GetCollection(
            uriTemplate: '/cars/{id}/opponents',
            requirements: [
                'id' => '\d+',
            ],
            security: "is_granted('ROLE_USER')",
            output: MatchmakingOpponentOutput::class,
            provider: MatchmakingOpponentProvider::class,
            paginationEnabled: false,
            normalizationContext: [
                'groups' => ['matchmaking:read'],
            ],
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
    public const BASE_XP_REQUIRED = 100;
    public const XP_INCREASE_PER_LEVEL = 50;

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

    #[ORM\Column(options: ['default' => 1000])]
    #[Groups(['car:read'])]
    private int $rating = 1000;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['car:read'])]
    private int $wins = 0;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['car:read'])]
    private int $losses = 0;

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

    #[Groups(['car:read'])]
    public function getXpRequiredForNextLevel(): int
    {
        return self::BASE_XP_REQUIRED
            + (($this->level - 1) * self::XP_INCREASE_PER_LEVEL);
    }

    #[Groups(['car:read'])]
    public function getXpProgressPercent(): float
    {
        $requiredXp = $this->getXpRequiredForNextLevel();

        if ($requiredXp <= 0) {
            return 0.0;
        }

        return min(
            100.0,
            round(($this->xp / $requiredXp) * 100, 2)
        );
    }

    public function addXp(int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException(
                'Le montant d’XP doit être supérieur à zéro.'
            );
        }

        $this->xp += $amount;
        $this->touch();
    }

    public function canLevelUp(): bool
    {
        return $this->xp >= $this->getXpRequiredForNextLevel();
    }

    public function levelUp(): void
    {
        if (!$this->canLevelUp()) {
            throw new \DomainException(
                'La voiture ne possède pas assez d’XP.'
            );
        }

        $requiredXp = $this->getXpRequiredForNextLevel();

        $this->xp -= $requiredXp;
        ++$this->level;

        $this->touch();
    }

    public function addMoney(int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException(
                'Le montant d’argent doit être supérieur à zéro.'
            );
        }

        $this->money += $amount;
        $this->touch();
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function getLosses(): int
    {
        return $this->losses;
    }

    #[Groups(['car:read'])]
    public function getDuelsPlayed(): int
    {
        return $this->wins + $this->losses;
    }

    #[Groups(['car:read'])]
    public function getWinRate(): float
    {
        $duelsPlayed = $this->getDuelsPlayed();

        if ($duelsPlayed === 0) {
            return 0.0;
        }

        return round(
            ($this->wins / $duelsPlayed) * 100,
            2
        );
    }

    public function recordWin(int $ratingDelta): void
    {
        if ($ratingDelta < 0) {
            throw new \InvalidArgumentException(
                'Le gain de classement ne peut pas être négatif.'
            );
        }

        $this->rating += $ratingDelta;
        ++$this->wins;

        $this->touch();
    }

    public function recordLoss(int $ratingDelta): void
    {
        if ($ratingDelta > 0) {
            throw new \InvalidArgumentException(
                'La perte de classement ne peut pas être positive.'
            );
        }

        $this->rating += $ratingDelta;
        ++$this->losses;

        $this->touch();
    }
}
