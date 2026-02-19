<?php

namespace App\Command;

use App\Repository\PersonnageRepository;
use App\Service\SvgPersonnageRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-personnage-images',
    description: 'Génère les images SVG manquantes pour les personnages sans image (données SQL de base).',
)]
class GeneratePersonnageImagesCommand extends Command
{
    public function __construct(
        private PersonnageRepository $personnageRepository,
        private SvgPersonnageRenderer $svgRenderer,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $personnages = $this->personnageRepository->createQueryBuilder('p')
            ->where('p.image IS NULL')
            ->getQuery()
            ->getResult();

        if (empty($personnages)) {
            $io->success('Tous les personnages ont déjà une image.');
            return Command::SUCCESS;
        }

        $io->progressStart(count($personnages));
        $errors = 0;

        foreach ($personnages as $personnage) {
            try {
                $svgData = $this->svgRenderer->generate($personnage);
                $personnage->setImage($svgData);
            } catch (\Exception $e) {
                $errors++;
            }

            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();

        $count = count($personnages) - $errors;
        $io->success("$count image(s) générée(s)." . ($errors ? " $errors erreur(s)." : ''));

        return Command::SUCCESS;
    }
}
