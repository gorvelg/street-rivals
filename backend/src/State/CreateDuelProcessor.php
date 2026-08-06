<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreateDuelInput;
use App\Entity\Duel;
use App\Entity\User;
use App\Repository\CarRepository;
use App\Service\CarProgressionService;
use App\Service\DuelRewardCalculator;
use App\Service\DuelSimulator;
use App\Service\RatingCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<CreateDuelInput, Duel>
 */
final class CreateDuelProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly CarRepository $carRepository,
        private readonly DuelSimulator $duelSimulator,
        private readonly DuelRewardCalculator $rewardCalculator,
        private readonly RatingCalculator $ratingCalculator,
        private readonly CarProgressionService $progressionService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): Duel {
        /*
         * Vérification du DTO reçu.
         */
        if (
            !$data instanceof CreateDuelInput
            || $data->attackerCarId === null
            || $data->defenderCarId === null
        ) {
            throw new BadRequestHttpException(
                'Les deux voitures sont obligatoires.'
            );
        }

        /*
         * Vérification de l’utilisateur connecté.
         */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.'
            );
        }

        /*
         * Chargement de la voiture attaquante.
         */
        $attacker = $this->carRepository->find(
            $data->attackerCarId
        );

        if ($attacker === null) {
            throw new NotFoundHttpException(
                'La voiture attaquante est introuvable.'
            );
        }

        /*
         * Chargement de la voiture défensive.
         */
        $defender = $this->carRepository->find(
            $data->defenderCarId
        );

        if ($defender === null) {
            throw new NotFoundHttpException(
                'La voiture défensive est introuvable.'
            );
        }

        /*
         * Le joueur connecté doit obligatoirement posséder
         * la voiture attaquante.
         */
        if ($attacker->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException(
                'La voiture attaquante ne vous appartient pas.'
            );
        }

        /*
         * Une voiture ne peut pas se battre contre elle-même.
         */
        if ($attacker->getId() === $defender->getId()) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Une voiture ne peut pas s’affronter elle-même.'
            );
        }

        /*
         * Les duels classés entre deux voitures appartenant
         * au même compte sont interdits.
         */
        if ($defender->getUser()?->getId() === $user->getId()) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Un duel classé contre une voiture du même compte est interdit.'
            );
        }

        /*
         * Simulation du duel.
         *
         * Cette étape ne modifie pas la base de données.
         */
        try {
            $simulation = $this->duelSimulator->simulate(
                attacker: $attacker,
                defender: $defender,
            );
        } catch (\DomainException $exception) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $exception->getMessage(),
                $exception
            );
        }

        /*
         * Calcul des récompenses en fonction du gagnant.
         */
        $rewards = $this->rewardCalculator->calculate(
            attacker: $attacker,
            defender: $defender,
            winner: $simulation->winnerCar,
        );

        /*
         * Calcul des variations Elo avant de modifier
         * les classements des voitures.
         */
        $ratingResult = $this->ratingCalculator->calculate(
            attacker: $attacker,
            defender: $defender,
            winner: $simulation->winnerCar,
        );

        /*
         * Toutes les modifications sont enregistrées dans
         * une seule transaction :
         *
         * - argent ;
         * - XP ;
         * - éventuelle montée de niveau ;
         * - éventuel CardChoice ;
         * - classement Elo ;
         * - victoires et défaites ;
         * - création du duel.
         */
        return $this->entityManager->wrapInTransaction(
            function (
                EntityManagerInterface $entityManager
            ) use (
                $attacker,
                $defender,
                $simulation,
                $rewards,
                $ratingResult
            ): Duel {
                /*
                 * Attribution de l’argent.
                 */
                $attacker->addMoney(
                    $rewards->attackerMoney
                );

                $defender->addMoney(
                    $rewards->defenderMoney
                );

                /*
                 * Attribution de l’XP.
                 *
                 * applyXp() ne doit pas ouvrir une nouvelle transaction
                 * et ne doit pas exécuter de flush.
                 */
                $this->progressionService->applyXp(
                    car: $attacker,
                    amount: $rewards->attackerXp,
                );

                $this->progressionService->applyXp(
                    car: $defender,
                    amount: $rewards->defenderXp,
                );

                /*
                 * Mise à jour du classement et des statistiques
                 * de victoires/défaites.
                 */
                $attackerWon =
                    $simulation->winnerCar->getId()
                    === $attacker->getId();

                if ($attackerWon) {
                    $attacker->recordWin(
                        $ratingResult->attackerDelta
                    );

                    $defender->recordLoss(
                        $ratingResult->defenderDelta
                    );
                } else {
                    $attacker->recordLoss(
                        $ratingResult->attackerDelta
                    );

                    $defender->recordWin(
                        $ratingResult->defenderDelta
                    );
                }

                /*
                 * Complément du replay avec les récompenses.
                 */
                $replayData = $simulation->replayData;

                $replayData['rewards'] = [
                    'attacker' => [
                        'xp' => $rewards->attackerXp,
                        'money' => $rewards->attackerMoney,
                    ],
                    'defender' => [
                        'xp' => $rewards->defenderXp,
                        'money' => $rewards->defenderMoney,
                    ],
                ];

                /*
                 * Complément du replay avec l’évolution Elo.
                 */
                $replayData['ranking'] = [
                    'attacker' => [
                        'before' => $ratingResult->attackerBefore,
                        'after' => $ratingResult->attackerAfter,
                        'delta' => $ratingResult->attackerDelta,
                    ],
                    'defender' => [
                        'before' => $ratingResult->defenderBefore,
                        'after' => $ratingResult->defenderAfter,
                        'delta' => $ratingResult->defenderDelta,
                    ],
                ];

                /*
                 * Création du duel enregistré en base.
                 */
                $duel = new Duel(
                    attackerCar: $attacker,
                    defenderCar: $defender,
                    winnerCar: $simulation->winnerCar,
                    finalGap: $simulation->finalGap,
                    randomSeed: $simulation->randomSeed,
                    engineVersion: $simulation->engineVersion,
                    attackerSnapshot:
                    $simulation->attackerSnapshot,
                    defenderSnapshot:
                    $simulation->defenderSnapshot,
                    replayData: $replayData,

                    attackerXpReward:
                    $rewards->attackerXp,
                    attackerMoneyReward:
                    $rewards->attackerMoney,
                    defenderXpReward:
                    $rewards->defenderXp,
                    defenderMoneyReward:
                    $rewards->defenderMoney,

                    attackerRatingBefore:
                    $ratingResult->attackerBefore,
                    attackerRatingAfter:
                    $ratingResult->attackerAfter,
                    attackerRatingDelta:
                    $ratingResult->attackerDelta,

                    defenderRatingBefore:
                    $ratingResult->defenderBefore,
                    defenderRatingAfter:
                    $ratingResult->defenderAfter,
                    defenderRatingDelta:
                    $ratingResult->defenderDelta,
                );

                $entityManager->persist($duel);

                /*
                 * Aucun flush manuel :
                 * wrapInTransaction() exécutera le flush puis le commit.
                 */
                return $duel;
            }
        );
    }
}
