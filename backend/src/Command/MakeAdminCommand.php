<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:make-admin',
    description: 'Attribue le rôle administrateur à un utilisateur.'
)]
final class MakeAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'email',
            InputArgument::REQUIRED,
            'Adresse e-mail de l’utilisateur'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $email = mb_strtolower(
            trim((string) $input->getArgument('email'))
        );

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error(
                'L’adresse e-mail fournie est invalide.'
            );

            return Command::INVALID;
        }

        $user = $this->userRepository->findOneBy([
            'email' => $email,
        ]);

        if (!$user instanceof User) {
            $io->error(
                sprintf(
                    'Aucun utilisateur trouvé pour %s.',
                    $email
                )
            );

            return Command::FAILURE;
        }

        if ($user->isAdmin()) {
            $io->warning(
                sprintf(
                    '%s possède déjà le rôle ROLE_ADMIN.',
                    $email
                )
            );

            return Command::SUCCESS;
        }

        $user->addRole('ROLE_ADMIN');

        $this->entityManager->flush();

        $io->success(
            sprintf(
                '%s possède maintenant le rôle ROLE_ADMIN.',
                $email
            )
        );

        return Command::SUCCESS;
    }
}
