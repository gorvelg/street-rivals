<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreateDuelInput;
use App\Entity\Duel;
use App\Entity\User;
use App\Repository\CarRepository;
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

        /*
         * Le joueur doit obligatoirement contrôler l’attaquant.
         * La voiture défensive peut appartenir à un autre compte.
         */
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

        $duel = new Duel(
            attackerCar: $attacker,
            defenderCar: $defender,
            winnerCar: $simulation->winnerCar,
            finalGap: $simulation->finalGap,
            randomSeed: $simulation->randomSeed,
            engineVersion: $simulation->engineVersion,
            attackerSnapshot: $simulation->attackerSnapshot,
            defenderSnapshot: $simulation->defenderSnapshot,
            replayData: $simulation->replayData,
        );

        $this->entityManager->persist($duel);
        $this->entityManager->flush();

        return $duel;
    }
}
