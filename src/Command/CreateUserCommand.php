<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Créer un nouvel utilisateur',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Pseudo de l\'utilisateur')
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Créer un administrateur')
            ->addOption('employee', null, InputOption::VALUE_NONE, 'Créer un employé')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $user = new User();
        $user->setPseudo($username);
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        // Définir les rôles
        $roles = ['ROLE_USER'];
        if ($input->getOption('admin')) {
            $roles[] = 'ROLE_ADMIN';
            $io->note('L\'utilisateur sera administrateur');
        } elseif ($input->getOption('employee')) {
            $roles[] = 'ROLE_EMPLOYEE';
            $io->note('L\'utilisateur sera employé');
        }
        $user->setRoles($roles);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success(sprintf('Utilisateur "%s" créé avec succès !', $username));
            $io->table(
                ['Champ', 'Valeur'],
                [
                    ['Username', $username],
                    ['Email', $email],
                    ['Rôles', implode(', ', $user->getRoles())],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la création de l\'utilisateur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
