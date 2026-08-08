<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Duel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class DuelReplayController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    #[Route(
        '/api/duels/{id}/replay',
        name: 'api_duel_replay',
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

        if (
            !$user instanceof User
        ) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.'
            );
        }

        /*
         * =====================================
         * DUEL
         * =====================================
         */

        $duel =
            $this->entityManager
                ->getRepository(
                    Duel::class
                )
                ->find($id);

        if (
            !$duel instanceof Duel
        ) {
            throw new NotFoundHttpException(
                'Duel introuvable.'
            );
        }

        $attacker =
            $duel
                ->getAttackerCar();

        $defender =
            $duel
                ->getDefenderCar();

        /*
         * =====================================
         * AUTORISATION
         *
         * On autorise uniquement un utilisateur
         * possédant l'une des deux voitures.
         * =====================================
         */

        $ownsAttacker =
            $attacker
                ?->getUser()
                ?->getId()
            === $user->getId();

        $ownsDefender =
            $defender
                ?->getUser()
                ?->getId()
            === $user->getId();

        if (
            !$ownsAttacker
            && !$ownsDefender
        ) {
            throw new AccessDeniedHttpException(
                'Vous ne pouvez pas consulter ce duel.'
            );
        }

        /*
         * =====================================
         * POINT DE VUE DU JOUEUR
         * =====================================
         */

        $viewerSide =
            $ownsAttacker
                ? 'attacker'
                : 'defender';

        $viewerCar =
            $ownsAttacker
                ? $attacker
                : $defender;

        $viewerWon =
            $duel
                ->getWinnerCar()
                ?->getId()
            === $viewerCar
                ?->getId();

        /*
         * =====================================
         * RECOMPENSES / ELO
         * =====================================
         */

        if (
            $viewerSide === 'attacker'
        ) {
            $viewerRewards = [
                'xp' =>
                    $duel
                        ->getAttackerXpReward(),

                'money' =>
                    $duel
                        ->getAttackerMoneyReward(),
            ];

            $viewerRating = [
                'before' =>
                    $duel
                        ->getAttackerRatingBefore(),

                'after' =>
                    $duel
                        ->getAttackerRatingAfter(),

                'delta' =>
                    $duel
                        ->getAttackerRatingDelta(),
            ];
        } else {
            $viewerRewards = [
                'xp' =>
                    $duel
                        ->getDefenderXpReward(),

                'money' =>
                    $duel
                        ->getDefenderMoneyReward(),
            ];

            $viewerRating = [
                'before' =>
                    $duel
                        ->getDefenderRatingBefore(),

                'after' =>
                    $duel
                        ->getDefenderRatingAfter(),

                'delta' =>
                    $duel
                        ->getDefenderRatingDelta(),
            ];
        }

        /*
         * =====================================
         * RÉPONSE
         * =====================================
         */

        return $this->json([
            'id' =>
                $duel->getId(),

            'createdAt' =>
                $duel
                    ->getCreatedAt()
                    ->format(
                        DATE_ATOM
                    ),

            'engineVersion' =>
                $duel
                    ->getEngineVersion(),

            'finalGap' =>
                $duel
                    ->getFinalGap(),

            'winnerCarId' =>
                $duel
                    ->getWinnerCar()
                    ?->getId(),

            'viewerSide' =>
                $viewerSide,

            'viewerWon' =>
                $viewerWon,

            'viewerCarId' =>
                $viewerCar
                    ?->getId(),

            /*
             * Snapshots historiques.
             *
             * On affiche donc les voitures
             * telles qu'elles étaient au moment
             * du duel.
             */
            'attackerSnapshot' =>
                $duel
                    ->getAttackerSnapshot(),

            'defenderSnapshot' =>
                $duel
                    ->getDefenderSnapshot(),

            /*
             * Événements + narration
             * déterministes du duel original.
             */
            'replayData' =>
                $duel
                    ->getReplayData(),

            'viewerRewards' =>
                $viewerRewards,

            'viewerRating' =>
                $viewerRating,
        ]);
    }
}
