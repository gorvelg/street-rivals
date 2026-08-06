<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Car;
use App\Entity\Duel;
use App\Entity\GameEvent;
use App\Entity\User;
use App\Enum\GameEventType;
use Doctrine\ORM\EntityManagerInterface;

final class GameEventTracker
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Enregistre l’événement dans l’Unit of Work Doctrine,
     * mais n’exécute pas de flush.
     *
     * À utiliser dans les processeurs et transactions existants.
     *
     * @param array<string, mixed> $payload
     */
    public function track(
        GameEventType $type,
        ?User $user = null,
        ?Car $car = null,
        ?Duel $duel = null,
        array $payload = [],
    ): GameEvent {
        $event = new GameEvent(
            type: $type,
            user: $user,
            car: $car,
            duel: $duel,
            payload: $payload,
        );

        $this->entityManager->persist($event);

        return $event;
    }

    /**
     * Enregistre et sauvegarde immédiatement l’événement.
     *
     * À réserver aux événements indépendants, comme une connexion.
     *
     * @param array<string, mixed> $payload
     */
    public function trackAndFlush(
        GameEventType $type,
        ?User $user = null,
        ?Car $car = null,
        ?Duel $duel = null,
        array $payload = [],
    ): GameEvent {
        $event = $this->track(
            type: $type,
            user: $user,
            car: $car,
            duel: $duel,
            payload: $payload,
        );

        $this->entityManager->flush();

        return $event;
    }
}
