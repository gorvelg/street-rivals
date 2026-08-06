<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Duel;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class CurrentUserDuelExtension implements
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
        if ($resourceClass !== Duel::class) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $duelAlias = $queryBuilder->getRootAliases()[0];

        $attackerAlias = $queryNameGenerator
            ->generateJoinAlias('attacker_car');

        $defenderAlias = $queryNameGenerator
            ->generateJoinAlias('defender_car');

        $parameterName = $queryNameGenerator
            ->generateParameterName('current_user');

        $queryBuilder
            ->innerJoin(
                sprintf('%s.attackerCar', $duelAlias),
                $attackerAlias
            )
            ->innerJoin(
                sprintf('%s.defenderCar', $duelAlias),
                $defenderAlias
            )
            ->andWhere(sprintf(
                '%s.user = :%s OR %s.user = :%s',
                $attackerAlias,
                $parameterName,
                $defenderAlias,
                $parameterName
            ))
            ->setParameter($parameterName, $user)
            ->orderBy(
                sprintf('%s.createdAt', $duelAlias),
                'DESC'
            );
    }
}
