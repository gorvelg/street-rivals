<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\CarCard;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class CurrentUserCarCardExtension implements
    QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if ($resourceClass !== CarCard::class) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $carCardAlias = $queryBuilder->getRootAliases()[0];

        $carAlias = $queryNameGenerator
            ->generateJoinAlias('car');

        $parameterName = $queryNameGenerator
            ->generateParameterName('current_user');

        $queryBuilder
            ->innerJoin(
                sprintf('%s.car', $carCardAlias),
                $carAlias
            )
            ->andWhere(sprintf(
                '%s.user = :%s',
                $carAlias,
                $parameterName
            ))
            ->setParameter($parameterName, $user);
    }
}
