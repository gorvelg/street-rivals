<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\GenerateCardChoiceInput;
use App\Entity\CardChoice;
use App\Entity\User;
use App\Repository\CardChoiceRepository;
use App\Repository\CarRepository;
use App\Service\CardChoiceGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<GenerateCardChoiceInput, CardChoice>
 */
final class GenerateCardChoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly CarRepository $carRepository,
        private readonly CardChoiceRepository $cardChoiceRepository,
        private readonly CardChoiceGenerator $cardChoiceGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CardChoice {
        if (
            !$data instanceof GenerateCardChoiceInput
            || $data->carId === null
        ) {
            throw new BadRequestHttpException(
                'L’identifiant de la voiture est obligatoire.'
            );
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(
                'Utilisateur non authentifié.'
            );
        }

        $car = $this->carRepository->find($data->carId);

        if ($car === null) {
            throw new NotFoundHttpException(
                'Voiture introuvable.'
            );
        }

        if ($car->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException(
                'Cette voiture ne vous appartient pas.'
            );
        }

        if (
            $this->cardChoiceRepository
                ->findPendingForCar($car) instanceof CardChoice
        ) {
            throw new ConflictHttpException(
                'Cette voiture possède déjà un choix en attente.'
            );
        }

        try {
            $choice = $this->cardChoiceGenerator
                ->generateForCar($car);
        } catch (\DomainException $exception) {
            throw new ConflictHttpException(
                $exception->getMessage()
            );
        } catch (\RuntimeException $exception) {
            throw new HttpException(
                422,
                $exception->getMessage()
            );
        }

        $this->entityManager->flush();

        return $choice;
    }
}
