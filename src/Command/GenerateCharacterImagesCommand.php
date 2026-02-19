<?php

namespace App\Command;

use App\Repository\PersonnageRepository;
use App\Service\SvgPersonnageRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-character-images',
    description: 'Génère les images SVG pour les personnages qui n\'en ont pas encore.',
)]
class GenerateCharacterImagesCommand extends Command
{
    public function __construct(
        private readonly PersonnageRepository $personnageRepository,
        private readonly SvgPersonnageRenderer $renderer,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'all',
            null,
            InputOption::VALUE_NONE,
            'Régénérer toutes les images, y compris celles déjà existantes.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io  = new SymfonyStyle($input, $output);
        $all = $input->getOption('all');

        if ($all) {
            $personnages = $this->personnageRepository->findAll();
            $io->note(sprintf('Régénération forcée de %d personnage(s).', count($personnages)));
        } else {
            $personnages = $this->personnageRepository->findBy(['image' => null]);
            $io->note(sprintf('%d personnage(s) sans image trouvé(s).', count($personnages)));
        }

        if (empty($personnages)) {
            $io->success('Aucun personnage à traiter.');
            return Command::SUCCESS;
        }

        $io->progressStart(count($personnages));

        foreach ($personnages as $personnage) {
            $personnage->setImage($this->renderer->generate($personnage));
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();

        $io->success(sprintf('%d image(s) générée(s) avec succès.', count($personnages)));

        return Command::SUCCESS;
    }
}
