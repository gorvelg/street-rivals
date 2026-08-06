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

        /*
         * La simulation ne modifie pas la base.
         * On la calcule avant d’ouvrir la transaction pour que celle-ci
         * reste aussi courte que possible.
         */
        try {
            $simulation = $this->duelSimulator->simulate(
                attacker: $attacker,
                defender: $defender,
            );
        } catch (\DomainException $exception) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $exception->getMessage()
            );
        }

        $rewards = $this->rewardCalculator->calculate(
            attacker: $attacker,
            defender: $defender,
            winner: $simulation->winnerCar,
        );

        try {
            return $this->entityManager->wrapInTransaction(
                function (
                    EntityManagerInterface $entityManager
                ) use (
                    $attacker,
                    $defender,
                    $simulation,
                    $rewards
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
                     * Une montée de niveau peut créer un CardChoice.
                     */
                    $this->progressionService->applyXp(
                        $attacker,
                        $rewards->attackerXp
                    );

                    $this->progressionService->applyXp(
                        $defender,
                        $rewards->defenderXp
                    );

                    /*
                     * Les récompenses sont aussi placées dans le replay
                     * pour faciliter l’affichage côté frontend.
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
                    );

                    $entityManager->persist($duel);

                    /*
                     * wrapInTransaction() effectuera le flush
                     * et le commit si tout fonctionne.
                     */
                    return $duel;
                }
            );
        } catch (\RuntimeException|\DomainException $exception) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $exception->getMessage()
            );
        }
    }
}
