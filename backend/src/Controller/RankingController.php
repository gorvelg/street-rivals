<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Car;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class RankingController extends AbstractController
{
    private const MAXIMUM_RESULTS = 100;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    #[Route(
        '/api/ranking',
        name: 'api_ranking',
        methods: ['GET'],
    )]
    public function __invoke(
        Request $request,
    ): JsonResponse {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.'
            );
        }

        $repository =
            $this->entityManager
                ->getRepository(Car::class);

        /*
         * =====================================
         * TOP 100
         * =====================================
         */

        /** @var list<Car> $cars */
        $cars =
            $repository
                ->createQueryBuilder('car')
                ->orderBy(
                    'car.rating',
                    'DESC'
                )
                ->addOrderBy(
                    'car.wins',
                    'DESC'
                )
                ->addOrderBy(
                    'car.id',
                    'ASC'
                )
                ->setMaxResults(
                    self::MAXIMUM_RESULTS
                )
                ->getQuery()
                ->getResult();

        $items = [];

        foreach (
            $cars
            as $index => $car
        ) {
            $items[] =
                $this->serializeCar(
                    car: $car,
                    rank: $index + 1,
                );
        }

        /*
         * =====================================
         * VOITURE COURANTE
         * =====================================
         */

        $current = null;

        $requestedCarId =
            filter_var(
                $request->query->get(
                    'carId'
                ),
                FILTER_VALIDATE_INT
            );

        if (
            $requestedCarId !== false
            && $requestedCarId !== null
        ) {
            $selectedCar =
                $repository->find(
                    $requestedCarId
                );

            /*
             * On ne permet pas à un joueur
             * de demander le bloc "ma position"
             * d'une voiture ne lui appartenant pas.
             */
            if (
                $selectedCar instanceof Car
                && $selectedCar
                    ->getUser()
                    ?->getId()
                === $user->getId()
            ) {
                $higherRatedCars =
                    (int) $repository
                        ->createQueryBuilder(
                            'rankingCar'
                        )
                        ->select(
                            'COUNT(rankingCar.id)'
                        )
                        ->where(
                            'rankingCar.rating > :rating'
                        )
                        ->setParameter(
                            'rating',
                            $selectedCar->getRating()
                        )
                        ->getQuery()
                        ->getSingleScalarResult();

                $current =
                    $this->serializeCar(
                        car: $selectedCar,
                        rank:
                        $higherRatedCars
                        + 1,
                    );
            }
        }

        return $this->json([
            'items' =>
                $items,

            'current' =>
                $current,
        ]);
    }

    /**
     * @return array{
     *     rank: int,
     *     carId: int|null,
     *     pilotName: string,
     *     level: int,
     *     rating: int,
     *     wins: int,
     *     losses: int,
     *     color: string,
     *     bodyStyle: string,
     *     wheelStyle: string
     * }
     */
    private function serializeCar(
        Car $car,
        int $rank,
    ): array {
        return [
            'rank' =>
                $rank,

            'carId' =>
                $car->getId(),

            'pilotName' =>
                $car->getPilotName(),

            'level' =>
                $car->getLevel(),

            'rating' =>
                $car->getRating(),

            'wins' =>
                $car->getWins(),

            'losses' =>
                $car->getLosses(),

            'color' =>
                $car->getColor(),

            'bodyStyle' =>
                $car
                    ->getBodyStyle()
                    ->value,

            'wheelStyle' =>
                $car
                    ->getWheelStyle()
                    ->value,
        ];
    }
}
