<?php

namespace App\Entity;

use App\Repository\CarRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarRepository::class)]
class Car
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cars')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 32)]
    private ?string $pilot_name = null;

    #[ORM\Column(length: 7)]
    private ?string $color = null;

    #[ORM\Column]
    private ?int $money = 0;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $speed = 10;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $acceleration = 10;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $grip = 10;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $solidity = 10;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $level = 1;

    #[ORM\Column]
    private ?int $xp = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPilotName(): ?string
    {
        return $this->pilot_name;
    }

    public function setPilotName(string $pilot_name): static
    {
        $this->pilot_name = $pilot_name;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getMoney(): ?int
    {
        return $this->money;
    }

    public function setMoney(int $money): static
    {
        $this->money = $money;

        return $this;
    }

    public function getSpeed(): ?int
    {
        return $this->speed;
    }

    public function setSpeed(int $speed): static
    {
        $this->speed = $speed;

        return $this;
    }

    public function getAcceleration(): ?int
    {
        return $this->acceleration;
    }

    public function setAcceleration(int $acceleration): static
    {
        $this->acceleration = $acceleration;

        return $this;
    }

    public function getGrip(): ?int
    {
        return $this->grip;
    }

    public function setGrip(int $grip): static
    {
        $this->grip = $grip;

        return $this;
    }

    public function getSolidity(): ?int
    {
        return $this->solidity;
    }

    public function setSolidity(int $solidity): static
    {
        $this->solidity = $solidity;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getXp(): ?int
    {
        return $this->xp;
    }

    public function setXp(int $xp): static
    {
        $this->xp = $xp;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
