<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GameEvent;
use App\Enum\GameEventType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}
