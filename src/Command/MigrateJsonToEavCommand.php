<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\User;
use App\Service\EavService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:migrate:json-to-eav',
    description: 'Migre les données JSON vers le système EAV'
)]
class MigrateJsonToEavCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EavService $eavService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Afficher ce qui serait migré sans effectuer la migration')
            ->addOption('users', null, InputOption::VALUE_NONE, 'Migrer les métadonnées des utilisateurs')
            ->addOption('articles', null, InputOption::VALUE_NONE, 'Migrer les tags des articles')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Migrer toutes les données')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $dryRun = $input->getOption('dry-run');
        $migrateUsers = $input->getOption('users') || $input->getOption('all');
        $migrateArticles = $input->getOption('articles') || $input->getOption('all');

        if (!$migrateUsers && !$migrateArticles) {
            $io->error('Vous devez spécifier --users, --articles, ou --all');
            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->note('Mode DRY-RUN activé - Aucune modification ne sera effectuée');
        }

        $totalMigrated = 0;

        // Migration des métadonnées des utilisateurs
        if ($migrateUsers) {
            $io->section('Migration des métadonnées des utilisateurs');
            $totalMigrated += $this->migrateUserMetadata($io, $dryRun);
        }

        // Migration des tags des articles
        if ($migrateArticles) {
            $io->section('Migration des tags des articles');
            $totalMigrated += $this->migrateArticleTags($io, $dryRun);
        }

        if (!$dryRun && $totalMigrated > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Migration terminée ! %d enregistrements migrés.', $totalMigrated));
        } else {
            $io->info(sprintf('Migration simulée : %d enregistrements auraient été migrés.', $totalMigrated));
        }

        return Command::SUCCESS;
    }

    private function migrateUserMetadata(SymfonyStyle $io, bool $dryRun): int
    {
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('1=0') // Skip - metadata column no longer exists
            ->getQuery()
            ->getResult();

        $migratedCount = 0;

        foreach ($users as $user) {
            $metadata = $user->getMetadata();
            if (empty($metadata)) {
                continue;
            }

            $io->text(sprintf('Utilisateur %d (%s) : %d métadonnées', 
                $user->getId(), 
                $user->getEmail(), 
                count($metadata)
            ));

            if (!$dryRun) {
                // Injecter le service EAV dans l'entité
                $user->setEavService($this->eavService);
                
                // Migrer les métadonnées
                $user->migrateMetadataToEav();
                $migratedCount++;
            } else {
                foreach ($metadata as $key => $value) {
                    $io->text(sprintf('  - %s: %s', $key, is_array($value) ? json_encode($value) : $value));
                }
                $migratedCount++;
            }
        }

        $io->text(sprintf('Utilisateurs traités : %d', count($users)));
        return $migratedCount;
    }

    private function migrateArticleTags(SymfonyStyle $io, bool $dryRun): int
    {
        $articles = $this->entityManager->getRepository(Article::class)
            ->createQueryBuilder('a')
            ->where('a.tags IS NOT NULL')
            ->getQuery()
            ->getResult();

        $migratedCount = 0;

        foreach ($articles as $article) {
            $tags = $article->getTags();
            if (empty($tags)) {
                continue;
            }

            $io->text(sprintf('Article %d (%s) : %d tags', 
                $article->getId(), 
                $article->getTitle(), 
                count($tags)
            ));

            if (!$dryRun) {
                // Injecter le service EAV dans l'entité
                $article->setEavService($this->eavService);
                
                // Migrer les tags
                $article->migrateTagsToEav();
                $migratedCount++;
            } else {
                $io->text(sprintf('  - Tags: %s', implode(', ', $tags)));
                $migratedCount++;
            }
        }

        $io->text(sprintf('Articles traités : %d', count($articles)));
        return $migratedCount;
    }
}