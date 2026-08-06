<?php

namespace App\Repository;

use App\Entity\Car;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Car>
 */
class CarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Car::class);
    }

    public function findMatchmakingCandidates(
        Car $referenceCar,
        int $levelRange = 2,
        int $limit = 50,
    ): array {
        $referenceCarId = $referenceCar->getId();
        $owner = $referenceCar->getUser();

        if ($referenceCarId === null || $owner === null) {
            throw new \LogicException(
                'La voiture de référence doit être enregistrée.'
            );
        }

        $minimumLevel = max(
            1,
            $referenceCar->getLevel() - $levelRange
        );

        $maximumLevel =
            $referenceCar->getLevel() + $levelRange;

        return $this->createQueryBuilder('candidate')
            ->andWhere('candidate.id != :referenceCarId')
            ->andWhere('candidate.user != :owner')
            ->andWhere(
                'candidate.level BETWEEN :minimumLevel AND :maximumLevel'
            )
            ->setParameter('referenceCarId', $referenceCarId)
            ->setParameter('owner', $owner)
            ->setParameter('minimumLevel', $minimumLevel)
            ->setParameter('maximumLevel', $maximumLevel)
            ->orderBy('candidate.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Car>
     */
    public function findLeaderboard(int $limit = 100): array
    {
        return $this->createQueryBuilder('car')
            ->orderBy('car.rating', 'DESC')
            ->addOrderBy('car.wins', 'DESC')
            ->addOrderBy('car.level', 'DESC')
            ->addOrderBy('car.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
