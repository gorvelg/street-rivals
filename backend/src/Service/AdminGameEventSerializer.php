<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\GameEvent;

final class AdminGameEventSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(
        GameEvent $event,
    ): array {
        $user = $event->getUser();
        $car = $event->getCar();
        $duel = $event->getDuel();

        return [
            'id' => $event->getId(),

            'type' => $event->getType()->value,

            'occurredAt' => $event
                ->getOccurredAt()
                ->format(
                    \DateTimeInterface::ATOM,
                ),

            'user' => $user !== null
                ? [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'isActive' =>
                        $user->isActive(),
                ]
                : null,

            'car' => $car !== null
                ? [
                    'id' => $car->getId(),
                    'pilotName' =>
                        $car->getPilotName(),
                    'color' => $car->getColor(),
                ]
                : null,

            'duel' => $duel !== null
                ? [
                    'id' => $duel->getId(),
                ]
                : null,

            'payload' =>
                $event->getPayload() ?? [],
        ];
    }
}
