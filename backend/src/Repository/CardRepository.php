<?php

namespace App\Repository;

use App\Entity\Card;
use App\Entity\CarCard;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findAdminPage(
        ?string $search,
        ?string $type,
        ?string $rarity,
        ?int $tier,
        bool $equippedOnly,
        int $page,
        int $itemsPerPage,
    ): array {
        $queryBuilder = $this->createQueryBuilder('card')
            ->orderBy('card.name', 'ASC')
            ->addOrderBy('card.id', 'ASC')
            ->setFirstResult(
                ($page - 1) * $itemsPerPage
            )
            ->setMaxResults($itemsPerPage);

        $this->applyAdminFilters(
            queryBuilder: $queryBuilder,
            search: $search,
            type: $type,
            rarity: $rarity,
            tier: $tier,
            equippedOnly: $equippedOnly,
        );

        /** @var list<Card> $cards */
        $cards = $queryBuilder
            ->getQuery()
            ->getResult();

        $statsByCardId = $this->findAdminStatsForCards(
            $cards
        );

        return array_map(
            static function (Card $card) use (
                $statsByCardId
            ): array {
                $cardId = $card->getId();

                $stats = $cardId !== null
                    ? ($statsByCardId[$cardId] ?? null)
                    : null;

                return [
                    'id' => $cardId,
                    'code' => $card->getCode(),
                    'name' => $card->getName(),
                    'type' => $card->getType(),
                    'rarity' => $card->getRarity(),

                    'effectConfig' =>
                        $card->getEffectConfig() ?? [],

                    'carCount' =>
                        $stats['carCount'] ?? 0,

                    'tier1Count' =>
                        $stats['tier1Count'] ?? 0,

                    'tier2Count' =>
                        $stats['tier2Count'] ?? 0,

                    'tier3Count' =>
                        $stats['tier3Count'] ?? 0,

                    'equippedCount' =>
                        $stats['equippedCount'] ?? 0,

                    'averageAcquiredLevel' =>
                        $stats['averageAcquiredLevel']
                        ?? null,
                ];
            },
            $cards
        );
    }

    public function countForAdminSearch(
        ?string $search,
        ?string $type,
        ?string $rarity,
        ?int $tier,
        bool $equippedOnly,
    ): int {
        $queryBuilder = $this->createQueryBuilder('card')
            ->select('COUNT(card.id)');

        $this->applyAdminFilters(
            queryBuilder: $queryBuilder,
            search: $search,
            type: $type,
            rarity: $rarity,
            tier: $tier,
            equippedOnly: $equippedOnly,
        );

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<string>
     */
    public function findAdminTypes(): array
    {
        /** @var list<array{value: string|null}> $rows */
        $rows = $this->createQueryBuilder('card')
            ->select('DISTINCT card.type AS value')
            ->andWhere('card.type IS NOT NULL')
            ->andWhere("card.type <> ''")
            ->orderBy('card.type', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_values(
            array_filter(
                array_map(
                    static fn (array $row): ?string =>
                    isset($row['value'])
                        ? (string) $row['value']
                        : null,
                    $rows
                )
            )
        );
    }

    /**
     * @return list<string>
     */
    public function findAdminRarities(): array
    {
        /** @var list<array{value: string|null}> $rows */
        $rows = $this->createQueryBuilder('card')
            ->select('DISTINCT card.rarity AS value')
            ->andWhere('card.rarity IS NOT NULL')
            ->andWhere("card.rarity <> ''")
            ->orderBy('card.rarity', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_values(
            array_filter(
                array_map(
                    static fn (array $row): ?string =>
                    isset($row['value'])
                        ? (string) $row['value']
                        : null,
                    $rows
                )
            )
        );
    }

    private function applyAdminFilters(
        QueryBuilder $queryBuilder,
        ?string $search,
        ?string $type,
        ?string $rarity,
        ?int $tier,
        bool $equippedOnly,
    ): void {
        if ($search !== null && $search !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(card.name) LIKE :search
                OR LOWER(card.code) LIKE :search'
                )
                ->setParameter(
                    'search',
                    '%' . mb_strtolower($search) . '%'
                );
        }

        if ($type !== null && $type !== '') {
            $queryBuilder
                ->andWhere('card.type = :type')
                ->setParameter('type', $type);
        }

        if ($rarity !== null && $rarity !== '') {
            $queryBuilder
                ->andWhere('card.rarity = :rarity')
                ->setParameter('rarity', $rarity);
        }

        if ($tier !== null || $equippedOnly) {
            $subQuery = $this
                ->getEntityManager()
                ->createQueryBuilder()
                ->select('1')
                ->from(
                    CarCard::class,
                    'filterCarCard'
                )
                ->andWhere(
                    'filterCarCard.card = card'
                );

            if ($tier !== null) {
                $subQuery
                    ->andWhere(
                        'filterCarCard.tier = :adminTier'
                    );

                $queryBuilder->setParameter(
                    'adminTier',
                    $tier
                );
            }

            if ($equippedOnly) {
                $subQuery->andWhere(
                    'filterCarCard.isEquipped = true'
                );
            }

            $queryBuilder->andWhere(
                $queryBuilder
                    ->expr()
                    ->exists($subQuery->getDQL())
            );
        }
    }

    /**
     * @param list<Card> $cards
     *
     * @return array<int, array{
     *     carCount: int,
     *     tier1Count: int,
     *     tier2Count: int,
     *     tier3Count: int,
     *     equippedCount: int,
     *     averageAcquiredLevel: float|null
     * }>
     */
    private function findAdminStatsForCards(
        array $cards,
    ): array {
        if ($cards === []) {
            return [];
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select(
                'IDENTITY(carCard.card) AS cardId'
            )
            ->addSelect(
                'COUNT(DISTINCT car.id) AS carCount'
            )
            ->addSelect(
                'SUM(
                CASE
                    WHEN carCard.tier = 1
                    THEN 1
                    ELSE 0
                END
            ) AS tier1Count'
            )
            ->addSelect(
                'SUM(
                CASE
                    WHEN carCard.tier = 2
                    THEN 1
                    ELSE 0
                END
            ) AS tier2Count'
            )
            ->addSelect(
                'SUM(
                CASE
                    WHEN carCard.tier = 3
                    THEN 1
                    ELSE 0
                END
            ) AS tier3Count'
            )
            ->addSelect(
                'SUM(
                CASE
                    WHEN carCard.isEquipped = true
                    THEN 1
                    ELSE 0
                END
            ) AS equippedCount'
            )
            ->addSelect(
                'AVG(
                carCard.acquiredLevel
            ) AS averageAcquiredLevel'
            )
            ->from(CarCard::class, 'carCard')
            ->innerJoin('carCard.car', 'car')
            ->andWhere('carCard.card IN (:cards)')
            ->setParameter('cards', $cards)
            ->groupBy('carCard.card')
            ->getQuery()
            ->getScalarResult();

        $stats = [];

        foreach ($rows as $row) {
            $cardId = (int) $row['cardId'];

            $averageAcquiredLevel =
                array_key_exists(
                    'averageAcquiredLevel',
                    $row
                )
                && $row['averageAcquiredLevel'] !== null
                    ? round(
                    (float) $row[
                    'averageAcquiredLevel'
                    ],
                    2
                )
                    : null;

            $stats[$cardId] = [
                'carCount' =>
                    (int) $row['carCount'],

                'tier1Count' =>
                    (int) $row['tier1Count'],

                'tier2Count' =>
                    (int) $row['tier2Count'],

                'tier3Count' =>
                    (int) $row['tier3Count'],

                'equippedCount' =>
                    (int) $row['equippedCount'],

                'averageAcquiredLevel' =>
                    $averageAcquiredLevel,
            ];
        }

        return $stats;
    }

    /**
     * @return array{
     *     carCount: int,
     *     tier1Count: int,
     *     tier2Count: int,
     *     tier3Count: int,
     *     equippedCount: int,
     *     averageAcquiredLevel: float|null
     * }
     */
    public function findAdminStats(
        Card $card,
    ): array {
        $defaultStats = [
            'carCount' => 0,
            'tier1Count' => 0,
            'tier2Count' => 0,
            'tier3Count' => 0,
            'equippedCount' => 0,
            'averageAcquiredLevel' => null,
        ];

        $cardId = $card->getId();

        if ($cardId === null) {
            return $defaultStats;
        }

        $statsByCardId = $this->findAdminStatsForCards([
            $card,
        ]);

        return $statsByCardId[$cardId]
            ?? $defaultStats;
    }


}
