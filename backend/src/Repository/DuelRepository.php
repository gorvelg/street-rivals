<?php

namespace App\Repository;

use App\Entity\Car;
use App\Entity\Duel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Duel>
 */
class DuelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Duel::class);
    }

    public function countInitiatedByCarSince(
        Car $attacker,
        \DateTimeImmutable $since,
    ): int {
        return (int) $this->createQueryBuilder('duel')
            ->select('COUNT(duel.id)')
            ->andWhere('duel.attackerCar = :attacker')
            ->andWhere('duel.createdAt >= :since')
            ->setParameter('attacker', $attacker)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countBetweenCarsSince(
        Car $firstCar,
        Car $secondCar,
        \DateTimeImmutable $since,
    ): int {
        return (int) $this->createQueryBuilder('duel')
            ->select('COUNT(duel.id)')
            ->andWhere(
                '(
                duel.attackerCar = :firstCar
                AND duel.defenderCar = :secondCar
            ) OR (
                duel.attackerCar = :secondCar
                AND duel.defenderCar = :firstCar
            )'
            )
            ->andWhere('duel.createdAt >= :since')
            ->setParameter('firstCar', $firstCar)
            ->setParameter('secondCar', $secondCar)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLatestBetweenCars(
        Car $firstCar,
        Car $secondCar,
    ): ?Duel {
        return $this->createQueryBuilder('duel')
            ->andWhere(
                '(
                duel.attackerCar = :firstCar
                AND duel.defenderCar = :secondCar
            ) OR (
                duel.attackerCar = :secondCar
                AND duel.defenderCar = :firstCar
            )'
            )
            ->setParameter('firstCar', $firstCar)
            ->setParameter('secondCar', $secondCar)
            ->orderBy('duel.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
