<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Dto\GenerateCardChoiceInput;
use App\Dto\SelectCardChoiceInput;
use App\Repository\CardChoiceRepository;
use App\State\GenerateCardChoiceProcessor;
use App\State\SelectCardChoiceProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CardChoiceRepository::class)]
#[ORM\Table(
    name: 'card_choice',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_card_choice_car_level',
            columns: ['car_id', 'level']
        ),
    ]
)]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            security: "object.getCar().getUser() == user",
            securityMessage: 'Ce choix de cartes ne vous appartient pas.'
        ),
        new Post(
            uriTemplate: '/card_choices/generate',
            name: 'generate_card_choice',
            status: Response::HTTP_CREATED,
            security: "is_granted('ROLE_USER')",
            input: GenerateCardChoiceInput::class,
            output: CardChoice::class,
            read: false,
            processor: GenerateCardChoiceProcessor::class
        ),
        new Post(
            uriTemplate: '/card_choices/{id}/select',
            name: 'select_card_choice',
            status: Response::HTTP_OK,
            security: "is_granted('ROLE_USER')",
            input: SelectCardChoiceInput::class,
            output: CardChoice::class,
            read: false,
            processor: SelectCardChoiceProcessor::class
        ),
    ],
    normalizationContext: [
        'groups' => ['card-choice:read'],
    ],
)]
class CardChoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['card-choice:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'car_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    #[Groups(['card-choice:read'])]
    private ?Car $car = null;

    #[ORM\Column(type: 'smallint')]
    #[Groups(['card-choice:read'])]
    private int $level;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'first_card_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    #[Groups(['card-choice:read'])]
    private ?Card $firstCard = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'second_card_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    #[Groups(['card-choice:read'])]
    private ?Card $secondCard = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'selected_card_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'RESTRICT'
    )]
    #[Groups(['card-choice:read'])]
    private ?Card $selectedCard = null;

    #[ORM\Column(name: 'created_at')]
    #[Groups(['card-choice:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'selected_at', nullable: true)]
    #[Groups(['card-choice:read'])]
    private ?\DateTimeImmutable $selectedAt = null;

    public function __construct(
        Car $car,
        int $level,
        Card $firstCard,
        Card $secondCard,
    ) {
        if ($firstCard === $secondCard) {
            throw new \InvalidArgumentException(
                'Les deux cartes proposées doivent être différentes.'
            );
        }

        $this->car = $car;
        $this->level = $level;
        $this->firstCard = $firstCard;
        $this->secondCard = $secondCard;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getFirstCard(): ?Card
    {
        return $this->firstCard;
    }

    public function getSecondCard(): ?Card
    {
        return $this->secondCard;
    }

    public function getSelectedCard(): ?Card
    {
        return $this->selectedCard;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSelectedAt(): ?\DateTimeImmutable
    {
        return $this->selectedAt;
    }

    #[Groups(['card-choice:read'])]
    public function isPending(): bool
    {
        return $this->selectedCard === null;
    }

    public function containsCard(Card $card): bool
    {
        return $this->firstCard === $card
            || $this->secondCard === $card;
    }

    public function selectCard(Card $card): void
    {
        if (!$this->isPending()) {
            throw new \DomainException(
                'Une carte a déjà été sélectionnée.'
            );
        }

        if (!$this->containsCard($card)) {
            throw new \DomainException(
                'Cette carte ne fait pas partie des cartes proposées.'
            );
        }

        $this->selectedCard = $card;
        $this->selectedAt = new \DateTimeImmutable();
    }
}
