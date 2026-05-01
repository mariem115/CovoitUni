<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:promote-admin',
    description: 'Grant ROLE_ADMIN to a user by email. Log out and log in again before opening /admin.',
)]
final class PromoteAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');

        $user = $this->users->findOneBy(['email' => $email]);
        if (null === $user) {
            $io->error(sprintf('No user found with email "%s".', $email));

            return Command::FAILURE;
        }

        $storedRoles = array_values(array_unique(array_filter(
            $user->getRoles(),
            static fn (string $role): bool => 'ROLE_USER' !== $role,
        )));

        if (\in_array('ROLE_ADMIN', $storedRoles, true)) {
            $io->warning(sprintf('User "%s" already has ROLE_ADMIN.', $email));

            return Command::SUCCESS;
        }

        $storedRoles[] = 'ROLE_ADMIN';
        $user->setRoles($storedRoles);
        $this->em->flush();

        $io->success(sprintf('ROLE_ADMIN granted to "%s". Log out, log in again, then open /admin.', $email));

        return Command::SUCCESS;
    }
}
