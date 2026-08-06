<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GameEvent;
use App\Enum\GameEventType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Entity\Car;

/**
 * @extends ServiceEntityRepository<GameEvent>
 */
final class GameEventRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, GameEvent::class);
    }

    public function countByTypeSince(
        GameEventType $type,
        \DateTimeImmutable $since,
    ): int {
        return (int) $this->createQueryBuilder('event')
            ->select('COUNT(event.id)')
            ->andWhere('event.type = :type')
            ->andWhere('event.occurredAt >= :since')
            ->setParameter('type', $type)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDistinctUsersByTypeSince(
        GameEventType $type,
        \DateTimeImmutable $since,
    ): int {
        return (int) $this->createQueryBuilder('event')
            ->select('COUNT(DISTINCT event.user)')
            ->andWhere('event.type = :type')
            ->andWhere('event.user IS NOT NULL')
            ->andWhere('event.occurredAt >= :since')
            ->setParameter('type', $type)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<GameEvent>
     */
    public function findRecentForUser(
        User $user,
        int $limit = 20,
    ): array {
        return $this->createQueryBuilder('event')
            ->andWhere('event.user = :user')
            ->setParameter('user', $user)
            ->orderBy('event.occurredAt', 'DESC')
            ->addOrderBy('event.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findLastForUserAndType(
        User $user,
        GameEventType $type,
    ): ?GameEvent {
        return $this->createQueryBuilder('event')
            ->andWhere('event.user = :user')
            ->andWhere('event.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('event.occurredAt', 'DESC')
            ->addOrderBy('event.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<GameEvent>
     */
    public function findRecentForCar(
        Car $car,
        int $limit = 20,
    ): array {
        return $this->createQueryBuilder('event')
            ->andWhere('event.car = :car')
            ->setParameter('car', $car)
            ->orderBy('event.occurredAt', 'DESC')
            ->addOrderBy('event.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
