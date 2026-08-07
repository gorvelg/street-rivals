<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\CardKind;
use App\Enum\EquipmentSlot;

#[ORM\Entity(repositoryClass: CardRepository::class)]
#[ORM\Table(name: 'card')]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            security: "is_granted('ROLE_USER') and object.isEnabled()",
            securityMessage: 'Cette carte n’est pas disponible.'
        ),
    ],
    normalizationContext: [
        'groups' => ['card:read'],
    ],
)]
class Card
{
    public const RARITIES = [
        'COMMON',
        'RARE',
        'EPIC',
        'LEGENDARY',
    ];

    public const TYPES = [
        'PASSIVE',
        'ACTIVE',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['card:read', 'card-choice:read'])]
    private ?int $id = null;

    /**
     * Identifiant technique stable.
     *
     * Exemple : turbo_stage_1
     */
    #[ORM\Column(length: 64, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[a-z0-9_]+$/',
        message: 'Le code doit contenir uniquement des minuscules, chiffres et underscores.'
    )]
    #[Groups(['card:read', 'card-choice:read'])]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['card:read', 'card-choice:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['card:read', 'card-choice:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::RARITIES)]
    #[Groups(['card:read', 'card-choice:read'])]
    private string $rarity = 'COMMON';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::TYPES)]
    #[Groups(['card:read', 'card-choice:read'])]
    private string $type = 'PASSIVE';

    /**
     * Configuration interprétée plus tard par le moteur de jeu.
     */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['card:read', 'card-choice:read'])]
    private array $effectConfig = [];

    /**
     * Permet de désactiver une carte sans la supprimer.
     */
    #[ORM\Column]
    private bool $isEnabled = true;

    #[ORM\Column(
        length: 32,
        enumType: CardKind::class,
        options: [
            'default' => 'ability',
        ],
    )]
    #[Groups([
        'card:read',
        'card-choice:read',
    ])]
    private CardKind $kind = CardKind::ABILITY;

    #[ORM\Column(
        length: 32,
        nullable: true,
        enumType: EquipmentSlot::class,
    )]
    #[Groups([
        'card:read',
        'card-choice:read',
    ])]
    private ?EquipmentSlot $equipmentSlot = null;

    #[ORM\Column(
        options: [
            'default' => 3,
        ],
    )]
    #[Groups([
        'card:read',
        'card-choice:read',
    ])]
    private int $maxTier = 3;

    #[ORM\Column]
    #[Groups(['card:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['card:read'])]
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtolower(trim($code));

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = trim($description);

        return $this;
    }

    public function getRarity(): string
    {
        return $this->rarity;
    }

    public function setRarity(string $rarity): static
    {
        $rarity = strtoupper($rarity);

        if (!in_array($rarity, self::RARITIES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Rareté inconnue : %s.', $rarity)
            );
        }

        $this->rarity = $rarity;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $type = strtoupper($type);

        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Type de carte inconnu : %s.', $type)
            );
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEffectConfig(): array
    {
        return $this->effectConfig;
    }

    /**
     * @param array<string, mixed> $effectConfig
     */
    public function setEffectConfig(array $effectConfig): static
    {
        $this->effectConfig = $effectConfig;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setEnabled(bool $isEnabled): static
    {
        $this->isEnabled = $isEnabled;

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
    public function getKind(): CardKind
    {
        return $this->kind;
    }

    public function setKind(
        CardKind $kind,
    ): self {
        $this->kind = $kind;

        /*
         * Une capacité ou un bonus permanent
         * ne peut pas occuper un emplacement
         * d’équipement.
         */
        if (!$kind->requiresEquipmentSlot()) {
            $this->equipmentSlot = null;
        }

        return $this;
    }

    public function getEquipmentSlot(): ?EquipmentSlot
    {
        return $this->equipmentSlot;
    }

    public function setEquipmentSlot(
        ?EquipmentSlot $equipmentSlot,
    ): self {
        if (
            $equipmentSlot !== null
            && $this->kind !== CardKind::EQUIPMENT
        ) {
            throw new \LogicException(
                'Seule une carte de type équipement peut posséder un emplacement.',
            );
        }

        $this->equipmentSlot = $equipmentSlot;

        return $this;
    }

    public function getMaxTier(): int
    {
        return $this->maxTier;
    }

    public function setMaxTier(
        int $maxTier,
    ): self {
        if ($maxTier < 1 || $maxTier > 10) {
            throw new \InvalidArgumentException(
                'Le palier maximal doit être compris entre 1 et 10.',
            );
        }

        $this->maxTier = $maxTier;

        return $this;
    }

    public function isAbility(): bool
    {
        return $this->kind === CardKind::ABILITY;
    }

    public function isEquipment(): bool
    {
        return $this->kind === CardKind::EQUIPMENT;
    }

    public function isStatBoost(): bool
    {
        return $this->kind === CardKind::STAT_BOOST;
    }

    public function hasEquipmentSlot(): bool
    {
        return $this->equipmentSlot !== null;
    }
    public function validateConfiguration(): void
    {
        if (
            $this->kind === CardKind::EQUIPMENT
            && $this->equipmentSlot === null
        ) {
            throw new \LogicException(
                'Une carte de type équipement doit posséder un emplacement.',
            );
        }

        if (
            $this->kind !== CardKind::EQUIPMENT
            && $this->equipmentSlot !== null
        ) {
            throw new \LogicException(
                'Une carte qui n’est pas un équipement ne peut pas posséder d’emplacement.',
            );
        }

        if ($this->maxTier < 1 || $this->maxTier > 10) {
            throw new \LogicException(
                'Le palier maximal doit être compris entre 1 et 10.',
            );
        }
    }
}
