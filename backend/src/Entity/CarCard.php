<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Repository\CarCardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CarCardRepository::class)]
#[ORM\Table(
    name: 'car_card',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_car_card',
            columns: ['car_id', 'card_id'],
        ),
    ],
)]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
        ),
        new Get(
            security: "object.getCar().getUser() == user",
            securityMessage: 'Cette carte ne vous appartient pas.',
        ),
        new Patch(
            security: "object.getCar().getUser() == user",
            securityMessage: 'Cette carte ne vous appartient pas.',
        ),
    ],
    normalizationContext: [
        'groups' => ['car-card:read'],
    ],
    denormalizationContext: [
        'groups' => ['car-card:write'],
    ],
)]
class CarCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['car-card:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'car_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    #[Groups(['car-card:read'])]
    private ?Car $car = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'card_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT',
    )]
    #[Groups(['car-card:read'])]
    private ?Card $card = null;

    #[ORM\Column(name: 'is_equipped')]
    #[Groups(['car-card:read', 'car-card:write'])]
    private bool $isEquipped = true;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(['car-card:read'])]
    private int $tier = 1;

    #[ORM\Column(name: 'acquired_level')]
    #[Assert\Positive]
    #[Groups(['car-card:read'])]
    private int $acquiredLevel = 1;

    #[ORM\Column(name: 'acquired_at')]
    #[Groups(['car-card:read'])]
    private \DateTimeImmutable $acquiredAt;

    public function __construct()
    {
        $this->acquiredAt =
            new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(
        Car $car,
    ): static {
        $this->car = $car;

        return $this;
    }

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(
        Card $card,
    ): static {
        $this->card = $card;

        return $this;
    }

    public function isEquipped(): bool
    {
        return $this->isEquipped;
    }

    public function setEquipped(
        bool $isEquipped,
    ): static {
        $this->isEquipped = $isEquipped;

        return $this;
    }

    public function getTier(): int
    {
        return $this->tier;
    }

    public function getAcquiredLevel(): int
    {
        return $this->acquiredLevel;
    }

    public function setAcquiredLevel(
        int $acquiredLevel,
    ): static {
        if ($acquiredLevel < 1) {
            throw new \InvalidArgumentException(
                'Le niveau d’acquisition doit être supérieur ou égal à 1.',
            );
        }

        $this->acquiredLevel =
            $acquiredLevel;

        return $this;
    }

    public function getAcquiredAt():
    \DateTimeImmutable {
        return $this->acquiredAt;
    }

    public function setAcquiredAt(
        \DateTimeImmutable $acquiredAt,
    ): static {
        $this->acquiredAt = $acquiredAt;

        return $this;
    }

    /**
     * Indique si cette carte possédée
     * peut encore monter de palier.
     *
     * Le palier maximal n'est plus défini
     * dans CarCard.
     *
     * Il dépend maintenant directement
     * de la Card associée.
     */
    public function canUpgrade(): bool
    {
        $card = $this->getCard();

        if (!$card instanceof Card) {
            return false;
        }

        return $this->tier
            < $card->getMaxTier();
    }

    /**
     * Augmente la carte d'un palier.
     *
     * La limite dépend de Card::maxTier.
     */
    public function upgrade(): void
    {
        $card = $this->getCard();

        if (!$card instanceof Card) {
            throw new \LogicException(
                'Impossible d’améliorer une CarCard sans carte associée.',
            );
        }

        $maxTier = $card->getMaxTier();

        if (!$this->canUpgrade()) {
            throw new \DomainException(
                sprintf(
                    'Cette carte a déjà atteint le palier maximal %d.',
                    $maxTier,
                ),
            );
        }

        ++$this->tier;
    }
}
