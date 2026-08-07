<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Card;
use App\Entity\CarCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct(
            $registry,
            Card::class,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findAdminPage(
        ?string $search,
        ?string $type,
        ?string $rarity,
        ?string $kind,
        ?string $equipmentSlot,
        ?int $tier,
        bool $equippedOnly,
        int $page,
        int $itemsPerPage,
    ): array {
        $queryBuilder = $this
            ->createQueryBuilder('card')
            ->orderBy(
                'card.name',
                'ASC',
            )
            ->addOrderBy(
                'card.id',
                'ASC',
            )
            ->setFirstResult(
                ($page - 1)
                * $itemsPerPage,
            )
            ->setMaxResults(
                $itemsPerPage,
            );

        $this->applyAdminFilters(
            queryBuilder: $queryBuilder,
            search: $search,
            type: $type,
            rarity: $rarity,
            kind: $kind,
            equipmentSlot: $equipmentSlot,
            tier: $tier,
            equippedOnly: $equippedOnly,
        );

        /** @var list<Card> $cards */
        $cards = $queryBuilder
            ->getQuery()
            ->getResult();

        $statsByCardId =
            $this->findAdminStatsForCards(
                $cards,
            );

        return array_map(
            static function (
                Card $card,
            ) use (
                $statsByCardId,
            ): array {
                $cardId =
                    $card->getId();

                $stats =
                    $cardId !== null
                        ? (
                        $statsByCardId[
                        $cardId
                        ]
                        ?? null
                    )
                        : null;

                return [
                    'id' =>
                        $cardId,

                    'code' =>
                        $card->getCode(),

                    'name' =>
                        $card->getName(),

                    'type' =>
                        $card->getType(),

                    'rarity' =>
                        $card->getRarity(),

                    'kind' =>
                        $card
                            ->getKind()
                            ->value,

                    'equipmentSlot' =>
                        $card
                            ->getEquipmentSlot()
                            ?->value,

                    'maxTier' =>
                        $card->getMaxTier(),

                    'effectConfig' =>
                        $card->getEffectConfig()
                        ?? [],

                    'carCount' =>
                        $stats['carCount']
                        ?? 0,

                    'tierCounts' =>
                        $stats['tierCounts']
                        ?? self::createEmptyTierCounts(
                            $card->getMaxTier(),
                        ),

                    'equippedCount' =>
                        $stats['equippedCount']
                        ?? 0,

                    'averageAcquiredLevel' =>
                        $stats[
                        'averageAcquiredLevel'
                        ]
                        ?? null,
                ];
            },
            $cards,
        );
    }

    public function countForAdminSearch(
        ?string $search,
        ?string $type,
        ?string $rarity,
        ?string $kind,
        ?string $equipmentSlot,
        ?int $tier,
        bool $equippedOnly,
    ): int {
        $queryBuilder = $this
            ->createQueryBuilder('card')
            ->select(
                'COUNT(card.id)',
            );

        $this->applyAdminFilters(
            queryBuilder: $queryBuilder,
            search: $search,
            type: $type,
            rarity: $rarity,
            kind: $kind,
            equipmentSlot: $equipmentSlot,
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
        $rows = $this
            ->createQueryBuilder('card')
            ->select(
                'DISTINCT card.type AS value',
            )
            ->andWhere(
                'card.type IS NOT NULL',
            )
            ->andWhere(
                "card.type <> ''",
            )
            ->orderBy(
                'card.type',
                'ASC',
            )
            ->getQuery()
            ->getScalarResult();

        return array_values(
            array_filter(
                array_map(
                    static fn (
                        array $row,
                    ): ?string =>
                    isset($row['value'])
                        ? (string) $row['value']
                        : null,
                    $rows,
                ),
            ),
        );
    }

    /**
     * @return list<string>
     */
    public function findAdminRarities(): array
    {
        /** @var list<array{value: string|null}> $rows */
        $rows = $this
            ->createQueryBuilder('card')
            ->select(
                'DISTINCT card.rarity AS value',
            )
            ->andWhere(
                'card.rarity IS NOT NULL',
            )
            ->andWhere(
                "card.rarity <> ''",
            )
            ->orderBy(
                'card.rarity',
                'ASC',
            )
            ->getQuery()
            ->getScalarResult();

        return array_values(
            array_filter(
                array_map(
                    static fn (
                        array $row,
                    ): ?string =>
                    isset($row['value'])
                        ? (string) $row['value']
                        : null,
                    $rows,
                ),
            ),
        );
    }

    private function applyAdminFilters(
        QueryBuilder $queryBuilder,
        ?string $search,
        ?string $type,
        ?string $rarity,
        ?string $kind,
        ?string $equipmentSlot,
        ?int $tier,
        bool $equippedOnly,
    ): void {
        if (
            $search !== null
            && $search !== ''
        ) {
            $queryBuilder
                ->andWhere(
                    'LOWER(card.name) LIKE :search
                    OR LOWER(card.code) LIKE :search',
                )
                ->setParameter(
                    'search',
                    '%'
                    . mb_strtolower($search)
                    . '%',
                );
        }

        if (
            $type !== null
            && $type !== ''
        ) {
            $queryBuilder
                ->andWhere(
                    'card.type = :type',
                )
                ->setParameter(
                    'type',
                    $type,
                );
        }

        if (
            $rarity !== null
            && $rarity !== ''
        ) {
            $queryBuilder
                ->andWhere(
                    'card.rarity = :rarity',
                )
                ->setParameter(
                    'rarity',
                    $rarity,
                );
        }

        if (
            $kind !== null
            && $kind !== ''
        ) {
            $queryBuilder
                ->andWhere(
                    'card.kind = :kind',
                )
                ->setParameter(
                    'kind',
                    $kind,
                );
        }

        if (
            $equipmentSlot !== null
            && $equipmentSlot !== ''
        ) {
            $queryBuilder
                ->andWhere(
                    'card.equipmentSlot = :equipmentSlot',
                )
                ->setParameter(
                    'equipmentSlot',
                    $equipmentSlot,
                );
        }

        if (
            $tier !== null
            || $equippedOnly
        ) {
            $subQuery = $this
                ->getEntityManager()
                ->createQueryBuilder()
                ->select('1')
                ->from(
                    CarCard::class,
                    'filterCarCard',
                )
                ->andWhere(
                    'filterCarCard.card = card',
                );

            if ($tier !== null) {
                $subQuery
                    ->andWhere(
                        'filterCarCard.tier = :adminTier',
                    );

                $queryBuilder
                    ->setParameter(
                        'adminTier',
                        $tier,
                    );
            }

            if ($equippedOnly) {
                $subQuery
                    ->andWhere(
                        'filterCarCard.isEquipped = true',
                    );
            }

            $queryBuilder
                ->andWhere(
                    $queryBuilder
                        ->expr()
                        ->exists(
                            $subQuery->getDQL(),
                        ),
                );
        }
    }

    /**
     * @param list<Card> $cards
     *
     * @return array<int, array{
     *     carCount: int,
     *     tierCounts: array<int, int>,
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

        /*
         * On initialise toutes les cartes.
         *
         * Cela garantit que même une carte
         * jamais obtenue possède :
         *
         * tierCounts = {
         *   1: 0,
         *   2: 0,
         *   ...
         * }
         */
        $stats = [];

        foreach ($cards as $card) {
            $cardId = $card->getId();

            if ($cardId === null) {
                continue;
            }

            $stats[$cardId] = [
                'carCount' => 0,

                'tierCounts' =>
                    self::createEmptyTierCounts(
                        $card->getMaxTier(),
                    ),

                'equippedCount' => 0,

                'averageAcquiredLevel' =>
                    null,
            ];
        }

        /*
         * Statistiques générales.
         */
        /** @var list<array<string, mixed>> $summaryRows */
        $summaryRows = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select(
                'IDENTITY(carCard.card) AS cardId',
            )
            ->addSelect(
                'COUNT(DISTINCT car.id) AS carCount',
            )
            ->addSelect(
                'SUM(
                    CASE
                        WHEN carCard.isEquipped = true
                        THEN 1
                        ELSE 0
                    END
                ) AS equippedCount',
            )
            ->addSelect(
                'AVG(
                    carCard.acquiredLevel
                ) AS averageAcquiredLevel',
            )
            ->from(
                CarCard::class,
                'carCard',
            )
            ->innerJoin(
                'carCard.car',
                'car',
            )
            ->andWhere(
                'carCard.card IN (:cards)',
            )
            ->setParameter(
                'cards',
                $cards,
            )
            ->groupBy(
                'carCard.card',
            )
            ->getQuery()
            ->getScalarResult();

        foreach ($summaryRows as $row) {
            $cardId =
                (int) $row['cardId'];

            if (
                !array_key_exists(
                    $cardId,
                    $stats,
                )
            ) {
                continue;
            }

            $averageAcquiredLevel =
                array_key_exists(
                    'averageAcquiredLevel',
                    $row,
                )
                && $row[
                'averageAcquiredLevel'
                ] !== null
                    ? round(
                    (float) $row[
                    'averageAcquiredLevel'
                    ],
                    2,
                )
                    : null;

            $stats[$cardId]['carCount'] =
                (int) $row['carCount'];

            $stats[$cardId]['equippedCount'] =
                (int) $row['equippedCount'];

            $stats[$cardId][
            'averageAcquiredLevel'
            ] = $averageAcquiredLevel;
        }

        /*
         * Comptage dynamique par palier.
         *
         * Plus aucun CASE tier=1/2/3.
         */
        /** @var list<array<string, mixed>> $tierRows */
        $tierRows = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select(
                'IDENTITY(carCard.card) AS cardId',
            )
            ->addSelect(
                'carCard.tier AS tier',
            )
            ->addSelect(
                'COUNT(carCard.id) AS tierCount',
            )
            ->from(
                CarCard::class,
                'carCard',
            )
            ->andWhere(
                'carCard.card IN (:cards)',
            )
            ->setParameter(
                'cards',
                $cards,
            )
            ->groupBy(
                'carCard.card',
            )
            ->addGroupBy(
                'carCard.tier',
            )
            ->getQuery()
            ->getScalarResult();

        foreach ($tierRows as $row) {
            $cardId =
                (int) $row['cardId'];

            $tier =
                (int) $row['tier'];

            if (
                !array_key_exists(
                    $cardId,
                    $stats,
                )
            ) {
                continue;
            }

            if (
                !array_key_exists(
                    $tier,
                    $stats[$cardId][
                    'tierCounts'
                    ],
                )
            ) {
                /*
                 * Donnée incohérente éventuelle :
                 * par exemple une CarCard T5 alors
                 * que la carte est maxTier 3.
                 *
                 * On ne l'expose pas dans les
                 * statistiques normales.
                 */
                continue;
            }

            $stats[$cardId][
            'tierCounts'
            ][$tier] =
                (int) $row['tierCount'];
        }

        return $stats;
    }

    /**
     * @return array{
     *     carCount: int,
     *     tierCounts: array<int, int>,
     *     equippedCount: int,
     *     averageAcquiredLevel: float|null
     * }
     */
    public function findAdminStats(
        Card $card,
    ): array {
        $defaultStats = [
            'carCount' => 0,

            'tierCounts' =>
                self::createEmptyTierCounts(
                    $card->getMaxTier(),
                ),

            'equippedCount' => 0,

            'averageAcquiredLevel' =>
                null,
        ];

        $cardId =
            $card->getId();

        if ($cardId === null) {
            return $defaultStats;
        }

        $statsByCardId =
            $this->findAdminStatsForCards([
                $card,
            ]);

        return $statsByCardId[
        $cardId
        ] ?? $defaultStats;
    }

    /**
     * @return array<int, int>
     */
    private static function createEmptyTierCounts(
        int $maxTier,
    ): array {
        $tierCounts = [];

        for (
            $tier = 1;
            $tier <= $maxTier;
            ++$tier
        ) {
            $tierCounts[$tier] = 0;
        }

        return $tierCounts;
    }
}
