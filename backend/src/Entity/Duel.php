<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Dto\CreateDuelInput;
use App\Repository\DuelRepository;
use App\State\CreateDuelProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DuelRepository::class)]
#[ORM\Table(name: 'duel')]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            security: "
                object.getAttackerCar().getUser() == user
                or object.getDefenderCar().getUser() == user
            ",
            securityMessage: 'Ce duel ne concerne aucune de vos voitures.'
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            input: CreateDuelInput::class,
            processor: CreateDuelProcessor::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['duel:read'],
    ],
)]
class Duel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['duel:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'attacker_car_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    #[Groups(['duel:read'])]
    private Car $attackerCar;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'defender_car_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    #[Groups(['duel:read'])]
    private Car $defenderCar;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'winner_car_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    #[Groups(['duel:read'])]
    private Car $winnerCar;

    /**
     * Positif : l'attaquant gagne.
     * Négatif : le défenseur gagne.
     */
    #[ORM\Column(name: 'final_gap')]
    #[Groups(['duel:read'])]
    private int $finalGap;

    #[ORM\Column(name: 'random_seed', length: 32)]
    #[Groups(['duel:read'])]
    private string $randomSeed;

    #[ORM\Column(name: 'engine_version', length: 20)]
    #[Groups(['duel:read'])]
    private string $engineVersion;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(name: 'attacker_snapshot', type: Types::JSON)]
    #[Groups(['duel:read'])]
    private array $attackerSnapshot;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(name: 'defender_snapshot', type: Types::JSON)]
    #[Groups(['duel:read'])]
    private array $defenderSnapshot;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(name: 'replay_data', type: Types::JSON)]
    #[Groups(['duel:read'])]
    private array $replayData;

    #[ORM\Column(name: 'created_at')]
    #[Groups(['duel:read'])]
    private \DateTimeImmutable $createdAt;
    #[ORM\Column(name: 'attacker_xp_reward')]
    #[Groups(['duel:read'])]
    private int $attackerXpReward;

    #[ORM\Column(name: 'attacker_money_reward')]
    #[Groups(['duel:read'])]
    private int $attackerMoneyReward;

    #[ORM\Column(name: 'defender_xp_reward')]
    #[Groups(['duel:read'])]
    private int $defenderXpReward;

    #[ORM\Column(name: 'defender_money_reward')]
    #[Groups(['duel:read'])]
    private int $defenderMoneyReward;

    /**
     * @param array<string, mixed> $attackerSnapshot
     * @param array<string, mixed> $defenderSnapshot
     * @param array<string, mixed> $replayData
     */
    public function __construct(
        Car $attackerCar,
        Car $defenderCar,
        Car $winnerCar,
        int $finalGap,
        string $randomSeed,
        string $engineVersion,
        array $attackerSnapshot,
        array $defenderSnapshot,
        array $replayData,
        int $attackerXpReward,
        int $attackerMoneyReward,
        int $defenderXpReward,
        int $defenderMoneyReward,
    ) {
        $this->attackerCar = $attackerCar;
        $this->defenderCar = $defenderCar;
        $this->winnerCar = $winnerCar;
        $this->finalGap = $finalGap;
        $this->randomSeed = $randomSeed;
        $this->engineVersion = $engineVersion;
        $this->attackerSnapshot = $attackerSnapshot;
        $this->defenderSnapshot = $defenderSnapshot;
        $this->replayData = $replayData;

        $this->attackerXpReward = $attackerXpReward;
        $this->attackerMoneyReward = $attackerMoneyReward;
        $this->defenderXpReward = $defenderXpReward;
        $this->defenderMoneyReward = $defenderMoneyReward;

        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttackerCar(): Car
    {
        return $this->attackerCar;
    }

    public function getDefenderCar(): Car
    {
        return $this->defenderCar;
    }

    public function getWinnerCar(): Car
    {
        return $this->winnerCar;
    }

    public function getFinalGap(): int
    {
        return $this->finalGap;
    }

    public function getRandomSeed(): string
    {
        return $this->randomSeed;
    }

    public function getEngineVersion(): string
    {
        return $this->engineVersion;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttackerSnapshot(): array
    {
        return $this->attackerSnapshot;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefenderSnapshot(): array
    {
        return $this->defenderSnapshot;
    }

    /**
     * @return array<string, mixed>
     */
    public function getReplayData(): array
    {
        return $this->replayData;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAttackerXpReward(): int
    {
        return $this->attackerXpReward;
    }

    public function getAttackerMoneyReward(): int
    {
        return $this->attackerMoneyReward;
    }

    public function getDefenderXpReward(): int
    {
        return $this->defenderXpReward;
    }

    public function getDefenderMoneyReward(): int
    {
        return $this->defenderMoneyReward;
    }
}
