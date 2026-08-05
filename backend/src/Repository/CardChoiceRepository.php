<?php

namespace App\Repository;

use App\Entity\Car;
use App\Entity\CardChoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardChoice>
 */
class CardChoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardChoice::class);
    }

    //    /**
    //     * @return CardChoice[] Returns an array of CardChoice objects
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

    //    public function findOneBySomeField($value): ?CardChoice
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findPendingForCar(
        Car $car,
        ?int $excludedChoiceId = null
    ): ?CardChoice {
        $queryBuilder = $this->createQueryBuilder('choice');

        $queryBuilder
            ->andWhere('choice.car = :car')
            ->andWhere('choice.selectedCard IS NULL')
            ->setParameter('car', $car)
            ->setMaxResults(1);

        if ($excludedChoiceId !== null) {
            $queryBuilder
                ->andWhere('choice.id != :excludedChoiceId')
                ->setParameter('excludedChoiceId', $excludedChoiceId);
        }

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }
}
