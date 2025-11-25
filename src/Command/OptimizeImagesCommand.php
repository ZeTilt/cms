<?php

namespace App\Command;

use App\Service\ImageOptimizerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:optimize-images',
    description: 'Optimise les images (compression + conversion WebP)',
)]
class OptimizeImagesCommand extends Command
{
    public function __construct(
        private ImageOptimizerService $imageOptimizer,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('directory', InputArgument::OPTIONAL, 'Dossier à optimiser (relatif à public/)', 'uploads/images')
            ->addOption('max-width', 'W', InputOption::VALUE_REQUIRED, 'Largeur maximale des images')
            ->addOption('max-height', 'H', InputOption::VALUE_REQUIRED, 'Hauteur maximale des images')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans modification')
            ->setHelp(<<<'HELP'
La commande <info>%command.name%</info> optimise toutes les images d'un dossier :
- Compression JPG/PNG (qualité 75%)
- Génération de versions WebP
- Redimensionnement optionnel

<info>Exemples d'utilisation :</info>

  Optimiser les images uploadées :
  <info>php %command.full_name%</info>

  Optimiser les images du carousel :
  <info>php %command.full_name% assets/images</info>

  Limiter la largeur à 1200px :
  <info>php %command.full_name% uploads/images --max-width=1200</info>

  Simulation (dry-run) :
  <info>php %command.full_name% --dry-run</info>

HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $directory = $input->getArgument('directory');
        $fullPath = $this->projectDir . '/public/' . ltrim($directory, '/');

        $maxWidth = $input->getOption('max-width') ? (int) $input->getOption('max-width') : null;
        $maxHeight = $input->getOption('max-height') ? (int) $input->getOption('max-height') : null;
        $dryRun = $input->getOption('dry-run');

        $io->title('Optimisation des images');
        $io->text("Dossier : <info>$fullPath</info>");

        if ($maxWidth) {
            $io->text("Largeur max : <info>{$maxWidth}px</info>");
        }
        if ($maxHeight) {
            $io->text("Hauteur max : <info>{$maxHeight}px</info>");
        }

        if (!is_dir($fullPath)) {
            $io->error("Le dossier n'existe pas : $fullPath");
            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->warning('Mode simulation (dry-run) - aucune modification ne sera effectuée');
            $this->showFilesToProcess($io, $fullPath);
            return Command::SUCCESS;
        }

        $io->section('Traitement en cours...');

        try {
            $results = $this->imageOptimizer->optimizeDirectory($fullPath, $maxWidth, $maxHeight);
        } catch (\Exception $e) {
            $io->error("Erreur : " . $e->getMessage());
            return Command::FAILURE;
        }

        // Afficher les résultats
        $io->section('Résultats');

        if (!empty($results['files'])) {
            $rows = [];
            foreach ($results['files'] as $file) {
                $rows[] = [
                    $file['file'],
                    $this->imageOptimizer->formatBytes($file['saved']),
                    $file['webp'] ?? '-',
                ];
            }

            $io->table(
                ['Fichier', 'Économisé', 'Version WebP'],
                $rows
            );
        }

        $io->success([
            "Images traitées : {$results['processed']}",
            "Images ignorées : {$results['skipped']}",
            "Espace économisé : " . $this->imageOptimizer->formatBytes($results['total_saved']),
        ]);

        if (!empty($results['errors'])) {
            $io->warning('Erreurs rencontrées :');
            foreach ($results['errors'] as $error) {
                $io->text("  - {$error['file']} : {$error['error']}");
            }
        }

        return Command::SUCCESS;
    }

    private function showFilesToProcess(SymfonyStyle $io, string $directory): void
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $files = [];

        foreach ($extensions as $ext) {
            $files = array_merge($files, glob("$directory/*.$ext"));
            $files = array_merge($files, glob("$directory/*." . strtoupper($ext)));
        }

        $io->section('Fichiers qui seraient traités :');

        $totalSize = 0;
        $rows = [];
        foreach ($files as $file) {
            if (str_contains($file, '_thumb') || str_contains($file, '.webp')) {
                continue;
            }
            $size = filesize($file);
            $totalSize += $size;
            $rows[] = [basename($file), $this->imageOptimizer->formatBytes($size)];
        }

        $io->table(['Fichier', 'Taille actuelle'], $rows);
        $io->text("Taille totale : <info>" . $this->imageOptimizer->formatBytes($totalSize) . "</info>");
    }
}
