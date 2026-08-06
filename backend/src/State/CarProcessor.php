<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Car;
use App\Entity\User;
use App\Enum\GameEventType;
use App\Service\GameEventTracker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<Car, Car>
 */
final class CarProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly GameEventTracker $eventTracker,
    ) {
    }

    /**
     * @param Car $data
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): Car {
        if (!$data instanceof Car) {
            throw new \InvalidArgumentException(
                'Le processeur attend une voiture.'
            );
        }

        $authenticatedUser = $this->security->getUser();

        if (!$authenticatedUser instanceof User) {
            throw new \LogicException(
                'Aucun utilisateur authentifié.'
            );
        }

        /*
         * Une voiture qui ne possède pas encore d’identifiant
         * est en cours de création.
         */
        $isCreation = $data->getId() === null;

        /*
         * Une nouvelle voiture n’a pas encore de propriétaire.
         * Le propriétaire est toujours imposé par le serveur.
         */
        if ($data->getUser() === null) {
            $data->setUser($authenticatedUser);
        }

        /*
         * Protection supplémentaire :
         * empêche d’enregistrer une voiture pour un autre compte.
         */
        if (
            $data->getUser()?->getId()
            !== $authenticatedUser->getId()
        ) {
            throw new \LogicException(
                'Cette voiture ne vous appartient pas.'
            );
        }

        $data->touch();

        $this->entityManager->persist($data);

        /*
         * L’événement est uniquement créé lors d’un POST,
         * pas lors d’une modification de la voiture.
         *
         * track() ne fait aucun flush.
         */
        if ($isCreation) {
            $this->eventTracker->track(
                type: GameEventType::CAR_CREATED,
                user: $authenticatedUser,
                car: $data,
                payload: [
                    'pilotName' => $data->getPilotName(),
                    'color' => $data->getColor(),
                    'initialLevel' => $data->getLevel(),
                    'initialStats' => [
                        'speed' => $data->getSpeed(),
                        'acceleration' => $data->getAcceleration(),
                        'grip' => $data->getGrip(),
                        'solidity' => $data->getSolidity(),
                    ],
                ],
            );
        }

        /*
         * La voiture et l’événement sont enregistrés ensemble.
         */
        $this->entityManager->flush();

        return $data;
    }
}
