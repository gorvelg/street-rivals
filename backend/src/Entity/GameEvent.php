<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\GameEventType;
use App\Repository\GameEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameEventRepository::class)]
#[ORM\Table(
    name: 'game_event',
    indexes: [
        new ORM\Index(
            name: 'idx_game_event_type_date',
            columns: ['type', 'occurred_at']
        ),
        new ORM\Index(
            name: 'idx_game_event_user_date',
            columns: ['user_id', 'occurred_at']
        ),
        new ORM\Index(
            name: 'idx_game_event_car_date',
            columns: ['car_id', 'occurred_at']
        ),
    ]
)]
class GameEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(
        length: 64,
        enumType: GameEventType::class
    )]
    private GameEventType $type;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'user_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'car_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?Car $car = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'duel_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?Duel $duel = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $payload = [];

    #[ORM\Column(name: 'occurred_at')]
    private \DateTimeImmutable $occurredAt;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        GameEventType $type,
        ?User $user = null,
        ?Car $car = null,
        ?Duel $duel = null,
        array $payload = [],
    ) {
        $this->type = $type;
        $this->user = $user;
        $this->car = $car;
        $this->duel = $duel;
        $this->payload = $payload;
        $this->occurredAt = new \DateTimeImmutable(
            'now',
            new \DateTimeZone('UTC')
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): GameEventType
    {
        return $this->type;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function getDuel(): ?Duel
    {
        return $this->duel;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
