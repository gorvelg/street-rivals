<?php

namespace App\Repository;

use App\Entity\Car;
use App\Entity\Duel;
use App\Entity\User;
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
    public function countForUser(
        User $user,
    ): int {
        return (int) $this->createQueryBuilder('duel')
            ->select('COUNT(duel.id)')
            ->innerJoin(
                'duel.attackerCar',
                'attacker'
            )
            ->innerJoin(
                'duel.defenderCar',
                'defender'
            )
            ->andWhere(
                'attacker.user = :user OR defender.user = :user'
            )
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Duel>
     */
    public function findRecentForUser(
        User $user,
        int $limit = 10,
    ): array {
        return $this->createQueryBuilder('duel')
            ->addSelect(
                'attacker',
                'defender',
                'winner'
            )
            ->innerJoin(
                'duel.attackerCar',
                'attacker'
            )
            ->innerJoin(
                'duel.defenderCar',
                'defender'
            )
            ->innerJoin(
                'duel.winnerCar',
                'winner'
            )
            ->andWhere(
                'attacker.user = :user OR defender.user = :user'
            )
            ->setParameter('user', $user)
            ->orderBy('duel.createdAt', 'DESC')
            ->addOrderBy('duel.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Duel>
     */
    public function findRecentForCar(
        Car $car,
        int $limit = 10,
    ): array {
        return $this->createQueryBuilder('duel')
            ->addSelect(
                'attacker',
                'defender',
                'winner'
            )
            ->innerJoin(
                'duel.attackerCar',
                'attacker'
            )
            ->innerJoin(
                'duel.defenderCar',
                'defender'
            )
            ->innerJoin(
                'duel.winnerCar',
                'winner'
            )
            ->andWhere(
                'duel.attackerCar = :car
            OR duel.defenderCar = :car'
            )
            ->setParameter('car', $car)
            ->orderBy('duel.createdAt', 'DESC')
            ->addOrderBy('duel.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Duel>
     */
    public function findRecentForCarSince(
        Car $car,
        \DateTimeImmutable $since,
    ): array {
        return $this->createQueryBuilder('duel')
            ->addSelect(
                'attacker',
                'defender'
            )
            ->innerJoin(
                'duel.attackerCar',
                'attacker'
            )
            ->innerJoin(
                'duel.defenderCar',
                'defender'
            )
            ->andWhere(
                'duel.attackerCar = :car
            OR duel.defenderCar = :car'
            )
            ->andWhere('duel.createdAt > :since')
            ->setParameter('car', $car)
            ->setParameter('since', $since)
            ->orderBy('duel.createdAt', 'DESC')
            ->addOrderBy('duel.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
