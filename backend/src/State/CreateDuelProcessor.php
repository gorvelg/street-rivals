<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreateDuelInput;
use App\Entity\Duel;
use App\Entity\User;
use App\Exception\DuelLimitException;
use App\Repository\CarRepository;
use App\Service\CarProgressionService;
use App\Service\DuelPolicyService;
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
        private readonly DuelPolicyService $duelPolicyService,
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
        if (
            !$data instanceof CreateDuelInput
            || $data->attackerCarId === null
            || $data->defenderCarId === null
        ) {
            throw new BadRequestHttpException(
                'Les deux voitures sont obligatoires.'
            );
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.'
            );
        }

        $attacker = $this->carRepository->find(
            $data->attackerCarId
        );

        if ($attacker === null) {
            throw new NotFoundHttpException(
                'La voiture attaquante est introuvable.'
            );
        }

        $defender = $this->carRepository->find(
            $data->defenderCarId
        );

        if ($defender === null) {
            throw new NotFoundHttpException(
                'La voiture défensive est introuvable.'
            );
        }

        if ($attacker->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException(
                'La voiture attaquante ne vous appartient pas.'
            );
        }

        if ($attacker->getId() === $defender->getId()) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Une voiture ne peut pas s’affronter elle-même.'
            );
        }

        if ($defender->getUser()?->getId() === $user->getId()) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Un duel classé contre une voiture du même compte est interdit.'
            );
        }

        /*
         * Vérification des limites et calcul des multiplicateurs.
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

        $rewards = $this->rewardCalculator->calculate(
            attacker: $attacker,
            defender: $defender,
            winner: $simulation->winnerCar,
            multiplier: $policyResult->rewardMultiplier,
        );

        $ratingResult = $this->ratingCalculator->calculate(
            attacker: $attacker,
            defender: $defender,
            winner: $simulation->winnerCar,
            multiplier: $policyResult->ratingMultiplier,
        );

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
                $attacker->addMoney(
                    $rewards->attackerMoney
                );

                $defender->addMoney(
                    $rewards->defenderMoney
                );

                $this->progressionService->applyXp(
                    car: $attacker,
                    amount: $rewards->attackerXp,
                );

                $this->progressionService->applyXp(
                    car: $defender,
                    amount: $rewards->defenderXp,
                );

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

                return $duel;
            }
        );
    }
}
