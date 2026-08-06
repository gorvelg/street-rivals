<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreateDuelInput;
use App\Entity\Duel;
use App\Entity\User;
use App\Enum\GameEventType;
use App\Exception\DuelLimitException;
use App\Repository\CarRepository;
use App\Service\CarProgressionService;
use App\Service\DuelPolicyService;
use App\Service\DuelRewardCalculator;
use App\Service\DuelSimulator;
use App\Service\GameEventTracker;
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
        private readonly DuelPolicyService $duelPolicyService,
        private readonly DuelSimulator $duelSimulator,
        private readonly DuelRewardCalculator $rewardCalculator,
        private readonly RatingCalculator $ratingCalculator,
        private readonly CarProgressionService $progressionService,
        private readonly EntityManagerInterface $entityManager,
        private readonly GameEventTracker $eventTracker,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): Duel {
        /*
         * Vérification des données reçues.
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
         * La voiture attaquante doit appartenir
         * à l’utilisateur connecté.
         */
        if ($attacker->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException(
                'La voiture attaquante ne vous appartient pas.'
            );
        }

        /*
         * Une voiture ne peut pas se combattre elle-même.
         */
        if ($attacker->getId() === $defender->getId()) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Une voiture ne peut pas s’affronter elle-même.'
            );
        }

        /*
         * Empêche un joueur de faire combattre deux voitures
         * de son propre compte en duel classé.
         */
        if ($defender->getUser()?->getId() === $user->getId()) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Un duel classé contre une voiture du même compte est interdit.'
            );
        }

        /*
         * Vérification :
         *
         * - du cooldown ;
         * - de la limite quotidienne ;
         * - de la limite par paire ;
         * - des multiplicateurs anti-farming.
         */
        try {
            $policyResult = $this->duelPolicyService
                ->assertCanStart(
                    attacker: $attacker,
                    defender: $defender,
                );
        } catch (DuelLimitException $exception) {
            throw new HttpException(
                Response::HTTP_TOO_MANY_REQUESTS,
                $exception->getMessage(),
                $exception,
                [
                    'Retry-After' => (string)
                    $exception->retryAfterSeconds,
                ]
            );
        }

        /*
         * Simulation complète du duel.
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
         * Calcul des récompenses.
         */
        $rewards = $this->rewardCalculator->calculate(
            attacker: $attacker,
            defender: $defender,
            winner: $simulation->winnerCar,
            multiplier: $policyResult->rewardMultiplier,
        );

        /*
         * Calcul des nouvelles valeurs de classement.
         */
        $ratingResult = $this->ratingCalculator->calculate(
            attacker: $attacker,
            defender: $defender,
            winner: $simulation->winnerCar,
            multiplier: $policyResult->ratingMultiplier,
        );

        /*
         * Toutes les modifications sont enregistrées
         * dans une même transaction :
         *
         * - argent ;
         * - XP ;
         * - montées de niveau ;
         * - statistiques de victoire et de défaite ;
         * - classement ;
         * - duel ;
         * - événement analytique.
         */
        return $this->entityManager->wrapInTransaction(
            function (
                EntityManagerInterface $entityManager
            ) use (
                $attacker,
                $defender,
                $simulation,
                $rewards,
                $ratingResult,
                $policyResult
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
                 * applyXp() peut également :
                 * - déclencher une montée de niveau ;
                 * - créer un choix de cartes ;
                 * - enregistrer un événement level_up.
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
                 * Identification du vainqueur.
                 */
                $attackerWon =
                    $simulation->winnerCar->getId()
                    === $attacker->getId();

                /*
                 * Mise à jour :
                 *
                 * - des victoires et défaites ;
                 * - du classement des deux voitures.
                 */
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
                 * Enrichissement du replay avec les récompenses.
                 */
                $replayData = $simulation->replayData;

                $replayData['rewards'] = [
                    'multiplier' =>
                        $policyResult->rewardMultiplier,

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
                 * Enrichissement du replay avec les variations
                 * du classement.
                 */
                $replayData['ranking'] = [
                    'multiplier' =>
                        $policyResult->ratingMultiplier,

                    'attacker' => [
                        'before' =>
                            $ratingResult->attackerBefore,

                        'after' =>
                            $ratingResult->attackerAfter,

                        'delta' =>
                            $ratingResult->attackerDelta,
                    ],

                    'defender' => [
                        'before' =>
                            $ratingResult->defenderBefore,

                        'after' =>
                            $ratingResult->defenderAfter,

                        'delta' =>
                            $ratingResult->defenderDelta,
                    ],
                ];

                /*
                 * Enrichissement du replay avec les données
                 * de la protection anti-farming.
                 */
                $replayData['antiFarming'] = [
                    'dailyDuelNumber' =>
                        $policyResult->dailyDuelNumber,

                    'pairDuelNumber' =>
                        $policyResult->pairDuelNumber,

                    'rewardMultiplier' =>
                        $policyResult->rewardMultiplier,

                    'ratingMultiplier' =>
                        $policyResult->ratingMultiplier,

                    'dailyLimit' =>
                        DuelPolicyService::MAX_DAILY_DUELS,

                    'pairDailyLimit' =>
                        DuelPolicyService::MAX_DAILY_PAIR_DUELS,

                    'cooldownSeconds' =>
                        DuelPolicyService::PAIR_COOLDOWN_SECONDS,

                    'dayTimezone' => 'UTC',
                ];

                /*
                 * Création du duel.
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
                 * Enregistrement de l’événement analytique.
                 *
                 * track() ne déclenche aucun flush.
                 * L’événement sera validé dans la même transaction
                 * que le duel et toutes les récompenses.
                 */
                $this->eventTracker->track(
                    type: GameEventType::DUEL_COMPLETED,
                    user: $attacker->getUser(),
                    car: $attacker,
                    duel: $duel,
                    payload: [
                        /*
                         * Informations générales.
                         */
                        'attackerCarId' =>
                            $attacker->getId(),

                        'defenderCarId' =>
                            $defender->getId(),

                        'winnerCarId' =>
                            $simulation->winnerCar->getId(),

                        'attackerWon' =>
                            $attackerWon,

                        'finalGap' =>
                            $simulation->finalGap,

                        'randomSeed' =>
                            $simulation->randomSeed,

                        'engineVersion' =>
                            $simulation->engineVersion,

                        /*
                         * Récompenses de l’attaquant.
                         */
                        'attackerXpReward' =>
                            $rewards->attackerXp,

                        'attackerMoneyReward' =>
                            $rewards->attackerMoney,

                        /*
                         * Récompenses du défenseur.
                         */
                        'defenderXpReward' =>
                            $rewards->defenderXp,

                        'defenderMoneyReward' =>
                            $rewards->defenderMoney,

                        /*
                         * Classement de l’attaquant.
                         */
                        'attackerRatingBefore' =>
                            $ratingResult->attackerBefore,

                        'attackerRatingAfter' =>
                            $ratingResult->attackerAfter,

                        'attackerRatingDelta' =>
                            $ratingResult->attackerDelta,

                        /*
                         * Classement du défenseur.
                         */
                        'defenderRatingBefore' =>
                            $ratingResult->defenderBefore,

                        'defenderRatingAfter' =>
                            $ratingResult->defenderAfter,

                        'defenderRatingDelta' =>
                            $ratingResult->defenderDelta,

                        /*
                         * Données anti-farming.
                         */
                        'dailyDuelNumber' =>
                            $policyResult->dailyDuelNumber,

                        'pairDuelNumber' =>
                            $policyResult->pairDuelNumber,

                        'rewardMultiplier' =>
                            $policyResult->rewardMultiplier,

                        'ratingMultiplier' =>
                            $policyResult->ratingMultiplier,
                    ],
                );

                return $duel;
            }
        );
    }
}
