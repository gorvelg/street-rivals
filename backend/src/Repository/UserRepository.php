<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Car;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
final class UserRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, User::class);
    }

    /**
     * @return list<array{
     *     id: int,
     *     email: string,
     *     roles: list<string>,
     *     isActive: bool,
     *     carCount: int
     * }>
     */
    public function findAdminPage(
        ?string $search,
        int $page,
        int $itemsPerPage,
    ): array {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select([
                'u.id AS id',
                'u.email AS email',
                'u.roles AS roles',
                'u.isActive AS isActive',
                'COUNT(car.id) AS carCount',
            ])
            ->leftJoin(
                Car::class,
                'car',
                'WITH',
                'car.user = u'
            )
            ->groupBy('u.id')
            ->addGroupBy('u.email')
            ->addGroupBy('u.roles')
            ->addGroupBy('u.isActive')
            ->orderBy('u.id', 'DESC')
            ->setFirstResult(
                ($page - 1) * $itemsPerPage
            )
            ->setMaxResults($itemsPerPage);

        if ($search !== null && $search !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(u.email) LIKE :search'
                )
                ->setParameter(
                    'search',
                    '%' . mb_strtolower($search) . '%'
                );
        }

        /** @var list<array<string, mixed>> $results */
        $results = $queryBuilder
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static function (array $result): array {
                $roles = $result['roles'] ?? [];

                /*
                 * Selon la configuration Doctrine/MariaDB,
                 * le champ JSON peut éventuellement revenir
                 * sous forme de chaîne.
                 */
                if (is_string($roles)) {
                    $decodedRoles = json_decode(
                        $roles,
                        true
                    );

                    $roles = is_array($decodedRoles)
                        ? $decodedRoles
                        : [];
                }

                if (!is_array($roles)) {
                    $roles = [];
                }

                $normalizedRoles = [];

                foreach ($roles as $role) {
                    if (!is_string($role)) {
                        continue;
                    }

                    $normalizedRoles[] = $role;
                }

                /*
                 * ROLE_USER est ajouté automatiquement
                 * par User::getRoles(), mais n’est pas
                 * forcément stocké dans la base.
                 */
                $normalizedRoles[] = 'ROLE_USER';

                return [
                    'id' => (int) $result['id'],
                    'email' => (string) $result['email'],
                    'roles' => array_values(
                        array_unique($normalizedRoles)
                    ),
                    'isActive' =>
                        (bool) $result['isActive'],
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
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)');

        if ($search !== null && $search !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(u.email) LIKE :search'
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
}
