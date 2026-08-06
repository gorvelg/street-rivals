<?php

namespace App\Repository;

use App\Entity\Car;
use App\Entity\Duel;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;


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
    /**
     * @return list<array{
     *     id: int,
     *     attacker: array{
     *         id: int,
     *         pilotName: string,
     *         color: string,
     *         ownerId: int|null,
     *         ownerEmail: string|null
     *     },
     *     defender: array{
     *         id: int,
     *         pilotName: string,
     *         color: string,
     *         ownerId: int|null,
     *         ownerEmail: string|null
     *     },
     *     winnerCarId: int,
     *     winnerSide: string,
     *     finalGap: float,
     *     engineVersion: string,
     *     attackerXpReward: int,
     *     attackerMoneyReward: int,
     *     defenderXpReward: int,
     *     defenderMoneyReward: int,
     *     attackerRatingDelta: int,
     *     defenderRatingDelta: int,
     *     createdAt: string
     * }>
     */
    public function findAdminPage(
        ?string $search,
        ?string $winnerSide,
        ?string $engineVersion,
        int $page,
        int $itemsPerPage,
    ): array {
        $queryBuilder = $this->createQueryBuilder('duel')
            ->select([
                'duel.id AS id',
                'duel.finalGap AS finalGap',
                'duel.engineVersion AS engineVersion',

                'duel.attackerXpReward AS attackerXpReward',
                'duel.attackerMoneyReward AS attackerMoneyReward',
                'duel.defenderXpReward AS defenderXpReward',
                'duel.defenderMoneyReward AS defenderMoneyReward',

                'duel.attackerRatingDelta AS attackerRatingDelta',
                'duel.defenderRatingDelta AS defenderRatingDelta',

                'duel.createdAt AS createdAt',

                'attacker.id AS attackerId',
                'attacker.pilotName AS attackerPilotName',
                'attacker.color AS attackerColor',

                'attackerOwner.id AS attackerOwnerId',
                'attackerOwner.email AS attackerOwnerEmail',

                'defender.id AS defenderId',
                'defender.pilotName AS defenderPilotName',
                'defender.color AS defenderColor',

                'defenderOwner.id AS defenderOwnerId',
                'defenderOwner.email AS defenderOwnerEmail',

                'winner.id AS winnerCarId',
            ])
            ->innerJoin(
                'duel.attackerCar',
                'attacker'
            )
            ->leftJoin(
                'attacker.user',
                'attackerOwner'
            )
            ->innerJoin(
                'duel.defenderCar',
                'defender'
            )
            ->leftJoin(
                'defender.user',
                'defenderOwner'
            )
            ->innerJoin(
                'duel.winnerCar',
                'winner'
            )
            ->orderBy('duel.createdAt', 'DESC')
            ->addOrderBy('duel.id', 'DESC')
            ->setFirstResult(
                ($page - 1) * $itemsPerPage
            )
            ->setMaxResults($itemsPerPage);

        $this->applyAdminFilters(
            queryBuilder: $queryBuilder,
            search: $search,
            winnerSide: $winnerSide,
            engineVersion: $engineVersion,
        );

        /** @var list<array<string, mixed>> $results */
        $results = $queryBuilder
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static function (array $result): array {
                $attackerId = (int) $result['attackerId'];
                $defenderId = (int) $result['defenderId'];
                $winnerCarId = (int) $result['winnerCarId'];

                return [
                    'id' => (int) $result['id'],

                    'attacker' => [
                        'id' => $attackerId,
                        'pilotName' =>
                            (string) $result['attackerPilotName'],
                        'color' =>
                            (string) $result['attackerColor'],
                        'ownerId' =>
                            isset($result['attackerOwnerId'])
                                ? (int) $result['attackerOwnerId']
                                : null,
                        'ownerEmail' =>
                            isset($result['attackerOwnerEmail'])
                                ? (string) $result['attackerOwnerEmail']
                                : null,
                    ],

                    'defender' => [
                        'id' => $defenderId,
                        'pilotName' =>
                            (string) $result['defenderPilotName'],
                        'color' =>
                            (string) $result['defenderColor'],
                        'ownerId' =>
                            isset($result['defenderOwnerId'])
                                ? (int) $result['defenderOwnerId']
                                : null,
                        'ownerEmail' =>
                            isset($result['defenderOwnerEmail'])
                                ? (string) $result['defenderOwnerEmail']
                                : null,
                    ],

                    'winnerCarId' => $winnerCarId,

                    'winnerSide' =>
                        $winnerCarId === $attackerId
                            ? 'attacker'
                            : 'defender',

                    'finalGap' =>
                        (float) $result['finalGap'],

                    'engineVersion' =>
                        (string) $result['engineVersion'],

                    'attackerXpReward' =>
                        (int) $result['attackerXpReward'],

                    'attackerMoneyReward' =>
                        (int) $result['attackerMoneyReward'],

                    'defenderXpReward' =>
                        (int) $result['defenderXpReward'],

                    'defenderMoneyReward' =>
                        (int) $result['defenderMoneyReward'],

                    'attackerRatingDelta' =>
                        (int) $result['attackerRatingDelta'],

                    'defenderRatingDelta' =>
                        (int) $result['defenderRatingDelta'],

                    'createdAt' =>
                        self::normalizeAdminDate(
                            $result['createdAt'] ?? null
                        ),
                ];
            },
            $results
        );
    }

    public function countForAdminSearch(
        ?string $search,
        ?string $winnerSide,
        ?string $engineVersion,
    ): int {
        $queryBuilder = $this->createQueryBuilder('duel')
            ->select('COUNT(duel.id)')
            ->innerJoin(
                'duel.attackerCar',
                'attacker'
            )
            ->leftJoin(
                'attacker.user',
                'attackerOwner'
            )
            ->innerJoin(
                'duel.defenderCar',
                'defender'
            )
            ->leftJoin(
                'defender.user',
                'defenderOwner'
            )
            ->innerJoin(
                'duel.winnerCar',
                'winner'
            );

        $this->applyAdminFilters(
            queryBuilder: $queryBuilder,
            search: $search,
            winnerSide: $winnerSide,
            engineVersion: $engineVersion,
        );

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function applyAdminFilters(
        \Doctrine\ORM\QueryBuilder $queryBuilder,
        ?string $search,
        ?string $winnerSide,
        ?string $engineVersion,
    ): void {
        if ($search !== null && $search !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(attacker.pilotName) LIKE :search
                OR LOWER(defender.pilotName) LIKE :search
                OR LOWER(attackerOwner.email) LIKE :search
                OR LOWER(defenderOwner.email) LIKE :search'
                )
                ->setParameter(
                    'search',
                    '%' . mb_strtolower($search) . '%'
                );
        }

        if ($winnerSide === 'attacker') {
            $queryBuilder->andWhere(
                'winner = attacker'
            );
        }

        if ($winnerSide === 'defender') {
            $queryBuilder->andWhere(
                'winner = defender'
            );
        }

        if (
            $engineVersion !== null
            && $engineVersion !== ''
        ) {
            $queryBuilder
                ->andWhere(
                    'duel.engineVersion = :engineVersion'
                )
                ->setParameter(
                    'engineVersion',
                    $engineVersion
                );
        }
    }

    private static function normalizeAdminDate(
        mixed $value,
    ): string {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(
                \DateTimeInterface::ATOM
            );
        }

        if (is_string($value) && $value !== '') {
            try {
                return (
                new \DateTimeImmutable($value)
                )->format(
                        \DateTimeInterface::ATOM
                    );
            } catch (\Throwable) {
                return $value;
            }
        }

        return '';
    }
}
