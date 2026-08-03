<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Car;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class CurrentUserCarExtension implements QueryCollectionExtensionInterface
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
        if ($resourceClass !== Car::class) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            /*
             * La route est normalement déjà protégée.
             * Cette condition empêche néanmoins toute fuite accidentelle.
             */
            $queryBuilder
                ->andWhere('1 = 0');

            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $parameterName = $queryNameGenerator
            ->generateParameterName('current_user');

        $queryBuilder
            ->andWhere(sprintf(
                '%s.user = :%s',
                $rootAlias,
                $parameterName
            ))
            ->setParameter($parameterName, $user);
    }
}
