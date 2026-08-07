<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\NarrativeContext;
use App\Enum\NarrativePhraseType;
use App\Repository\CardNarrativePhraseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(
    repositoryClass: CardNarrativePhraseRepository::class,
)]
#[ORM\Table(name: 'card_narrative_phrase')]
#[ORM\Index(
    columns: [
        'card_id',
        'type',
        'context',
        'is_enabled',
    ],
    name: 'idx_card_narrative_lookup',
)]
class CardNarrativePhrase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Card::class)]
    #[ORM\JoinColumn(
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private ?Card $card = null;

    #[ORM\Column(
        length: 32,
        enumType: NarrativePhraseType::class,
    )]
    private NarrativePhraseType $type =
        NarrativePhraseType::ACTIVATION;

    #[ORM\Column(
        length: 32,
        enumType: NarrativeContext::class,
        options: [
            'default' => 'any',
        ],
    )]
    private NarrativeContext $context =
        NarrativeContext::ANY;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    private string $text = '';

    /**
     * Poids utilisé lors de la sélection.
     *
     * Une phrase avec un poids 3 aura trois fois plus
     * de chances d'être sélectionnée qu'une phrase
     * avec un poids 1.
     */
    #[ORM\Column(
        options: [
            'default' => 1,
        ],
    )]
    #[Assert\Range(min: 1, max: 100)]
    private int $weight = 1;

    #[ORM\Column(
        name: 'is_enabled',
        options: [
            'default' => true,
        ],
    )]
    private bool $isEnabled = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
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

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(Card $card): self
    {
        $this->card = $card;

        return $this;
    }

    public function getType(): NarrativePhraseType
    {
        return $this->type;
    }

    public function setType(
        NarrativePhraseType $type,
    ): self {
        $this->type = $type;

        return $this;
    }

    public function getContext(): NarrativeContext
    {
        return $this->context;
    }

    public function setContext(
        NarrativeContext $context,
    ): self {
        $this->context = $context;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = trim($text);

        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): self
    {
        if ($weight < 1 || $weight > 100) {
            throw new \InvalidArgumentException(
                'Le poids doit être compris entre 1 et 100.',
            );
        }

        $this->weight = $weight;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->isEnabled = $enabled;

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
        $this->updatedAt =
            new \DateTimeImmutable();
    }
}
