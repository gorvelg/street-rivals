<?php

namespace App\Repository;

use App\Entity\Car;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findAdminPage(
        ?string $search,
        int $page,
        int $itemsPerPage,
    ): array {
        $queryBuilder = $this->createQueryBuilder('user')
            ->select([
                'user.id AS id',
                'user.email AS email',
                'user.roles AS roles',
                'COUNT(car.id) AS carCount',
            ])
            ->leftJoin(
                Car::class,
                'car',
                'WITH',
                'car.user = user'
            )
            ->groupBy('user.id')
            ->addGroupBy('user.email')
            ->addGroupBy('user.roles')
            ->orderBy('user.id', 'DESC')
            ->setFirstResult(
                ($page - 1) * $itemsPerPage
            )
            ->setMaxResults($itemsPerPage);

        if ($search !== null && $search !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(user.email) LIKE :search'
                )
                ->setParameter(
                    'search',
                    '%' . mb_strtolower($search) . '%'
                );
        }

        $results = $queryBuilder
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static function (array $result): array {
                $roles = $result['roles'] ?? [];

                if (!is_array($roles)) {
                    $roles = [];
                }

                /*
                 * ROLE_USER est ajouté automatiquement par
                 * User::getRoles(), mais il n’est pas forcément
                 * stocké dans la colonne JSON.
                 */
                $roles[] = 'ROLE_USER';

                return [
                    'id' => (int) $result['id'],
                    'email' => (string) $result['email'],
                    'roles' => array_values(
                        array_unique($roles)
                    ),
                    'carCount' =>
                        (int) $result['carCount'],
                ];
            },
            $results
        );
    }

    public function countForAdminSearch(
        ?string $search,
    ): int {
        $queryBuilder = $this->createQueryBuilder('user')
            ->select('COUNT(user.id)');

        if ($search !== null && $search !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(user.email) LIKE :search'
                )
                ->setParameter(
                    'search',
                    '%' . mb_strtolower($search) . '%'
                );
        }

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }


    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
