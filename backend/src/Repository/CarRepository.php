<?php

namespace App\Repository;

use App\Entity\Car;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\CarCard;
use App\Entity\Duel;

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
    /**
     * @return list<array{
     *     id: int,
     *     pilotName: string,
     *     color: string,
     *     owner: array{
     *         id: int,
     *         email: string,
     *         isActive: bool
     *     },
     *     level: int,
     *     xp: int,
     *     money: int,
     *     rating: int,
     *     wins: int,
     *     losses: int,
     *     cardCount: int,
     *     lastDuelAt: string|null
     * }>
     */
    public function findAdminPage(
        ?string $search,
        ?int $minLevel,
        ?int $minRating,
        int $page,
        int $itemsPerPage,
    ): array {
        $queryBuilder = $this->createQueryBuilder('car')
            ->select([
                'car.id AS id',
                'car.pilotName AS pilotName',
                'car.color AS color',
                'car.level AS level',
                'car.xp AS xp',
                'car.money AS money',
                'car.rating AS rating',
                'car.wins AS wins',
                'car.losses AS losses',

                'owner.id AS ownerId',
                'owner.email AS ownerEmail',
                'owner.isActive AS ownerIsActive',

                'COUNT(DISTINCT carCard.id) AS cardCount',

                'MAX(attackerDuel.createdAt) AS lastAttackerDuelAt',
                'MAX(defenderDuel.createdAt) AS lastDefenderDuelAt',
            ])
            ->innerJoin(
                'car.user',
                'owner'
            )
            ->leftJoin(
                CarCard::class,
                'carCard',
                'WITH',
                'carCard.car = car'
            )
            ->leftJoin(
                Duel::class,
                'attackerDuel',
                'WITH',
                'attackerDuel.attackerCar = car'
            )
            ->leftJoin(
                Duel::class,
                'defenderDuel',
                'WITH',
                'defenderDuel.defenderCar = car'
            )
            ->groupBy('car.id')
            ->addGroupBy('car.pilotName')
            ->addGroupBy('car.color')
            ->addGroupBy('car.level')
            ->addGroupBy('car.xp')
            ->addGroupBy('car.money')
            ->addGroupBy('car.rating')
            ->addGroupBy('car.wins')
            ->addGroupBy('car.losses')
            ->addGroupBy('owner.id')
            ->addGroupBy('owner.email')
            ->addGroupBy('owner.isActive')
            ->orderBy('car.id', 'DESC')
            ->setFirstResult(
                ($page - 1) * $itemsPerPage
            )
            ->setMaxResults($itemsPerPage);

        if ($search !== null && $search !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(car.pilotName) LIKE :search
                OR LOWER(owner.email) LIKE :search'
                )
                ->setParameter(
                    'search',
                    '%' . mb_strtolower($search) . '%'
                );
        }

        if ($minLevel !== null) {
            $queryBuilder
                ->andWhere('car.level >= :minLevel')
                ->setParameter('minLevel', $minLevel);
        }

        if ($minRating !== null) {
            $queryBuilder
                ->andWhere('car.rating >= :minRating')
                ->setParameter('minRating', $minRating);
        }

        /** @var list<array<string, mixed>> $results */
        $results = $queryBuilder
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static function (array $result): array {
                return [
                    'id' => (int) $result['id'],

                    'pilotName' =>
                        (string) $result['pilotName'],

                    'color' =>
                        (string) $result['color'],

                    'owner' => [
                        'id' =>
                            (int) $result['ownerId'],

                        'email' =>
                            (string) $result['ownerEmail'],

                        'isActive' =>
                            (bool) $result['ownerIsActive'],
                    ],

                    'level' =>
                        (int) $result['level'],

                    'xp' =>
                        (int) $result['xp'],

                    'money' =>
                        (int) $result['money'],

                    'rating' =>
                        (int) $result['rating'],

                    'wins' =>
                        (int) $result['wins'],

                    'losses' =>
                        (int) $result['losses'],

                    'cardCount' =>
                        (int) $result['cardCount'],

                    'lastDuelAt' =>
                        self::getLatestDate(
                            $result['lastAttackerDuelAt']
                            ?? null,
                            $result['lastDefenderDuelAt']
                            ?? null,
                        ),
                ];
            },
            $results
        );
    }

    public function countForAdminSearch(
        ?string $search,
        ?int $minLevel,
        ?int $minRating,
    ): int {
        $queryBuilder = $this->createQueryBuilder('car')
            ->select('COUNT(car.id)')
            ->innerJoin(
                'car.user',
                'owner'
            );

        if ($search !== null && $search !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(car.pilotName) LIKE :search
                OR LOWER(owner.email) LIKE :search'
                )
                ->setParameter(
                    'search',
                    '%' . mb_strtolower($search) . '%'
                );
        }

        if ($minLevel !== null) {
            $queryBuilder
                ->andWhere('car.level >= :minLevel')
                ->setParameter('minLevel', $minLevel);
        }

        if ($minRating !== null) {
            $queryBuilder
                ->andWhere('car.rating >= :minRating')
                ->setParameter('minRating', $minRating);
        }

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }

    private static function getLatestDate(
        mixed $firstDate,
        mixed $secondDate,
    ): ?string {
        $dates = [];

        foreach ([$firstDate, $secondDate] as $value) {
            if ($value instanceof \DateTimeInterface) {
                $dates[] = \DateTimeImmutable::createFromInterface(
                    $value
                );

                continue;
            }

            if (!is_string($value) || $value === '') {
                continue;
            }

            try {
                $dates[] = new \DateTimeImmutable($value);
            } catch (\Throwable) {
                // Date Doctrine invalide : valeur ignorée.
            }
        }

        if ($dates === []) {
            return null;
        }

        usort(
            $dates,
            static fn (
                \DateTimeImmutable $first,
                \DateTimeImmutable $second,
            ): int => $second <=> $first
        );

        return $dates[0]->format(
            \DateTimeInterface::ATOM
        );
    }
}
