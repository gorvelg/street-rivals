<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\CarRepository;
use App\Service\CarProgressionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:car:add-xp',
    description: 'Ajoute de l’XP à une voiture pour les tests.',
)]
final class AddCarXpCommand extends Command
{
    public function __construct(
        private readonly CarRepository $carRepository,
        private readonly CarProgressionService $progressionService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'carId',
                InputArgument::REQUIRED,
                'Identifiant de la voiture'
            )
            ->addArgument(
                'amount',
                InputArgument::REQUIRED,
                'Quantité d’XP à ajouter'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $carId = (int) $input->getArgument('carId');
        $amount = (int) $input->getArgument('amount');

        if ($carId <= 0 || $amount <= 0) {
            $io->error(
                'Les identifiants et le montant d’XP doivent être positifs.'
            );

            return Command::INVALID;
        }

        $car = $this->carRepository->find($carId);

        if ($car === null) {
            $io->error('Voiture introuvable.');

            return Command::FAILURE;
        }

        $oldLevel = $car->getLevel();
        $oldXp = $car->getXp();

        try {
            $newChoice = $this->progressionService
                ->grantXp($car, $amount);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->table(
            ['Information', 'Avant', 'Après'],
            [
                ['Niveau', $oldLevel, $car->getLevel()],
                ['XP', $oldXp, $car->getXp()],
                [
                    'XP nécessaire',
                    '-',
                    $car->getXpRequiredForNextLevel(),
                ],
            ]
        );

        if ($newChoice !== null) {
            $io->success(sprintf(
                'Montée au niveau %d. Un choix de cartes a été créé.',
                $car->getLevel()
            ));
        } else {
            $io->success(sprintf(
                '%d XP ajoutée(s). Aucune montée de niveau.',
                $amount
            ));
        }

        return Command::SUCCESS;
    }
}
