<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Car;
use App\Entity\Card;
use App\Entity\CarCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CarCard>
 */
class CarCardRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct(
            $registry,
            CarCard::class,
        );
    }

    /**
     * Retourne les cartes équipées d’une voiture.
     *
     * @return list<CarCard>
     */
    public function findEquippedByCar(
        Car $car,
    ): array {
        return $this->createQueryBuilder('carCard')
            ->addSelect('card')
            ->innerJoin(
                'carCard.card',
                'card',
            )
            ->andWhere(
                'carCard.car = :car',
            )
            ->andWhere(
                'carCard.isEquipped = :isEquipped',
            )
            ->setParameter(
                'car',
                $car,
            )
            ->setParameter(
                'isEquipped',
                true,
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne toutes les cartes d’une voiture
     * pour sa fiche administrateur.
     *
     * @return list<CarCard>
     */
    public function findForAdminCar(
        Car $car,
    ): array {
        return $this->createQueryBuilder('carCard')
            ->addSelect('card')
            ->innerJoin(
                'carCard.card',
                'card',
            )
            ->andWhere(
                'carCard.car = :car',
            )
            ->setParameter(
                'car',
                $car,
            )
            ->orderBy(
                'carCard.isEquipped',
                'DESC',
            )
            ->addOrderBy(
                'carCard.tier',
                'DESC',
            )
            ->addOrderBy(
                'card.name',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne une page de voitures possédant
     * une carte donnée.
     *
     * @return list<CarCard>
     */
    public function findAdminPageForCard(
        Card $card,
        ?string $search,
        ?int $tier,
        bool $equippedOnly,
        int $page,
        int $itemsPerPage,
    ): array {
        $queryBuilder = $this
            ->createQueryBuilder('carCard')
            ->addSelect(
                'car',
                'owner',
            )
            ->innerJoin(
                'carCard.car',
                'car',
            )
            ->leftJoin(
                'car.user',
                'owner',
            )
            ->andWhere(
                'carCard.card = :card',
            )
            ->setParameter(
                'card',
                $card,
            )
            ->orderBy(
                'carCard.isEquipped',
                'DESC',
            )
            ->addOrderBy(
                'carCard.tier',
                'DESC',
            )
            ->addOrderBy(
                'car.rating',
                'DESC',
            )
            ->addOrderBy(
                'car.id',
                'ASC',
            )
            ->setFirstResult(
                ($page - 1) * $itemsPerPage,
            )
            ->setMaxResults(
                $itemsPerPage,
            );

        $this->applyAdminCardFilters(
            queryBuilder: $queryBuilder,
            search: $search,
            tier: $tier,
            equippedOnly: $equippedOnly,
        );

        /** @var list<CarCard> $results */
        $results = $queryBuilder
            ->getQuery()
            ->getResult();

        return $results;
    }

    /**
     * Compte les voitures possédant une carte
     * selon les filtres administrateur.
     */
    public function countForAdminCard(
        Card $card,
        ?string $search,
        ?int $tier,
        bool $equippedOnly,
    ): int {
        $queryBuilder = $this
            ->createQueryBuilder('carCard')
            ->select(
                'COUNT(carCard.id)',
            )
            ->innerJoin(
                'carCard.car',
                'car',
            )
            ->leftJoin(
                'car.user',
                'owner',
            )
            ->andWhere(
                'carCard.card = :card',
            )
            ->setParameter(
                'card',
                $card,
            );

        $this->applyAdminCardFilters(
            queryBuilder: $queryBuilder,
            search: $search,
            tier: $tier,
            equippedOnly: $equippedOnly,
        );

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Applique les filtres communs à la liste
     * et au compteur des détenteurs d’une carte.
     */
    private function applyAdminCardFilters(
        QueryBuilder $queryBuilder,
        ?string $search,
        ?int $tier,
        bool $equippedOnly,
    ): void {
        if (
            $search !== null
            && $search !== ''
        ) {
            $queryBuilder
                ->andWhere(
                    'LOWER(car.pilotName) LIKE :search
                    OR LOWER(owner.email) LIKE :search',
                )
                ->setParameter(
                    'search',
                    '%' . mb_strtolower($search) . '%',
                );
        }

        if ($tier !== null) {
            $queryBuilder
                ->andWhere(
                    'carCard.tier = :tier',
                )
                ->setParameter(
                    'tier',
                    $tier,
                );
        }

        if ($equippedOnly) {
            $queryBuilder->andWhere(
                'carCard.isEquipped = true',
            );
        }
    }
}
