<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Card;
use App\Entity\CardNarrativePhrase;
use App\Enum\NarrativeContext;
use App\Enum\NarrativePhraseType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardNarrativePhrase>
 */
class CardNarrativePhraseRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct(
            $registry,
            CardNarrativePhrase::class,
        );
    }

    /**
     * @return list<CardNarrativePhrase>
     */
    public function findCandidates(
        Card $card,
        NarrativePhraseType $type,
        NarrativeContext $context,
    ): array {
        /*
         * On privilégie d'abord les phrases spécifiques
         * au contexte.
         */
        if ($context !== NarrativeContext::ANY) {
            $specific =
                $this->findEnabledByContext(
                    $card,
                    $type,
                    $context,
                );

            if ($specific !== []) {
                return $specific;
            }
        }

        /*
         * Sinon fallback sur les phrases génériques.
         */
        return $this->findEnabledByContext(
            $card,
            $type,
            NarrativeContext::ANY,
        );
    }

    /**
     * @return list<CardNarrativePhrase>
     */
    private function findEnabledByContext(
        Card $card,
        NarrativePhraseType $type,
        NarrativeContext $context,
    ): array {
        /** @var list<CardNarrativePhrase> $result */
        $result =
            $this->createQueryBuilder('phrase')
                ->andWhere('phrase.card = :card')
                ->andWhere('phrase.type = :type')
                ->andWhere('phrase.context = :context')
                ->andWhere('phrase.isEnabled = true')
                ->setParameter('card', $card)
                ->setParameter('type', $type)
                ->setParameter('context', $context)
                ->orderBy('phrase.id', 'ASC')
                ->getQuery()
                ->getResult();

        return $result;
    }
}
