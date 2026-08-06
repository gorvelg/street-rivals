<?php

namespace App\Repository;

use App\Entity\CarCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Car;

/**
 * @extends ServiceEntityRepository<CarCard>
 */
class CarCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CarCard::class);
    }

    //    /**
    //     * @return CarCard[] Returns an array of CarCard objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CarCard
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return list<CarCard>
     */
    public function findEquippedByCar(Car $car): array
    {
        return $this->createQueryBuilder('carCard')
            ->addSelect('card')
            ->innerJoin('carCard.card', 'card')
            ->andWhere('carCard.car = :car')
            ->andWhere('carCard.isEquipped = :isEquipped')
            ->setParameter('car', $car)
            ->setParameter('isEquipped', true)
            ->getQuery()
            ->getResult();
    }
    /**
     * @return list<CarCard>
     */
    /**
     * @return list<CarCard>
     */
    public function findForAdminCar(
        Car $car,
    ): array {
        return $this->createQueryBuilder('carCard')
            ->addSelect('card')
            ->innerJoin('carCard.card', 'card')
            ->andWhere('carCard.car = :car')
            ->setParameter('car', $car)
            ->orderBy('carCard.isEquipped', 'DESC')
            ->addOrderBy('carCard.tier', 'DESC')
            ->addOrderBy('card.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
