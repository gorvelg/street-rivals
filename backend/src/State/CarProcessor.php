<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Car;
use App\Entity\User;
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
    ) {
    }

    /**
     * @param Car $data
     *
     * @return Car
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
        if ($data->getUser() !== $authenticatedUser) {
            throw new \LogicException(
                'Cette voiture ne vous appartient pas.'
            );
        }

        $data->touch();

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
