<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Car;
use App\Entity\Duel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CarDuelHistoryController extends AbstractController
{
    private const HISTORY_LIMIT = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    #[Route(
        '/api/cars/{id}/duel-history',
        name: 'api_car_duel_history',
        requirements: [
            'id' => '\d+',
        ],
        methods: ['GET'],
    )]
    public function __invoke(
        int $id,
    ): JsonResponse {
        $user =
            $this->security
                ->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.'
            );
        }

        /*
         * =====================================
         * VOITURE
         * =====================================
         */

        $car =
            $this->entityManager
                ->getRepository(
                    Car::class
                )
                ->find($id);

        if (!$car instanceof Car) {
            throw new NotFoundHttpException(
                'Voiture introuvable.'
            );
        }

        if (
            $car
                ->getUser()
                ?->getId()
            !== $user->getId()
        ) {
            throw new AccessDeniedHttpException(
                'Cette voiture ne vous appartient pas.'
            );
        }

        /*
         * =====================================
         * 20 DERNIERS DUELS
         * =====================================
         */

        /** @var list<Duel> $duels */
        $duels =
            $this->entityManager
                ->getRepository(
                    Duel::class
                )
                ->createQueryBuilder(
                    'duel'
                )
                ->where(
                    'duel.attackerCar = :car'
                )
                ->orWhere(
                    'duel.defenderCar = :car'
                )
                ->setParameter(
                    'car',
                    $car
                )
                ->orderBy(
                    'duel.id',
                    'DESC'
                )
                ->setMaxResults(
                    self::HISTORY_LIMIT
                )
                ->getQuery()
                ->getResult();

        $items = [];

        foreach (
            $duels
            as $duel
        ) {
            $items[] =
                $this->serializeDuel(
                    duel: $duel,
                    car: $car,
                );
        }

        return $this->json([
            'items' =>
                $items,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDuel(
        Duel $duel,
        Car $car,
    ): array {
        $carId =
            $car->getId();

        $attacker =
            $duel->getAttackerCar();

        $defender =
            $duel->getDefenderCar();

        $winner =
            $duel->getWinnerCar();

        $isAttacker =
            $attacker?->getId()
            === $carId;

        $opponent =
            $isAttacker
                ? $defender
                : $attacker;

        $won =
            $winner?->getId()
            === $carId;

        /*
         * On utilise le snapshot du duel
         * pour conserver l'apparence du véhicule
         * au moment où le duel a eu lieu.
         */
        $snapshot =
            $isAttacker
                ? $duel
                ->getDefenderSnapshot()
                : $duel
                ->getAttackerSnapshot();

        return [
            'duelId' =>
                $duel->getId(),

            'result' =>
                $won
                    ? 'victory'
                    : 'defeat',

            'won' =>
                $won,

            'side' =>
                $isAttacker
                    ? 'attacker'
                    : 'defender',

            'opponent' => [
                'carId' =>
                    $opponent?->getId(),

                'pilotName' =>
                    $snapshot['pilotName']
                    ?? $opponent?->getPilotName()
                        ?? 'Pilote inconnu',

                'level' =>
                    $snapshot['level']
                    ?? $opponent?->getLevel()
                        ?? 1,

                'color' =>
                    $snapshot['color']
                    ?? $opponent?->getColor()
                        ?? '#E63946',

                'bodyStyle' =>
                    $snapshot['bodyStyle']
                    ?? $opponent
                        ?->getBodyStyle()
                        ?->value
                        ?? 'coupe_01',

                'wheelStyle' =>
                    $snapshot['wheelStyle']
                    ?? $opponent
                        ?->getWheelStyle()
                        ?->value
                        ?? 'street_01',
            ],

            'xpReward' =>
                $isAttacker
                    ? $duel
                    ->getAttackerXpReward()
                    : $duel
                    ->getDefenderXpReward(),

            'moneyReward' =>
                $isAttacker
                    ? $duel
                    ->getAttackerMoneyReward()
                    : $duel
                    ->getDefenderMoneyReward(),

            'ratingBefore' =>
                $isAttacker
                    ? $duel
                    ->getAttackerRatingBefore()
                    : $duel
                    ->getDefenderRatingBefore(),

            'ratingAfter' =>
                $isAttacker
                    ? $duel
                    ->getAttackerRatingAfter()
                    : $duel
                    ->getDefenderRatingAfter(),

            'ratingDelta' =>
                $isAttacker
                    ? $duel
                    ->getAttackerRatingDelta()
                    : $duel
                    ->getDefenderRatingDelta(),

            'finalGap' =>
                $duel->getFinalGap(),

            'engineVersion' =>
                $duel->getEngineVersion(),

            'createdAt' =>
                $duel
                    ->getCreatedAt()
                    ->format(
                        DATE_ATOM
                    ),
        ];
    }
}
