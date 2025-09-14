<?php

namespace App\Command;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-article-content',
    description: 'Nettoie le contenu des articles pour remplacer les éléments non supportés'
)]
class CleanArticleContentCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $articles = $this->entityManager->getRepository(Article::class)->findAll();
        $cleanedCount = 0;

        $io->progressStart(count($articles));

        foreach ($articles as $article) {
            $originalContent = $article->getContent();
            $cleanedContent = $this->cleanContent($originalContent);

            if ($originalContent !== $cleanedContent) {
                $article->setContent($cleanedContent);
                $cleanedCount++;
            }

            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        if ($cleanedCount > 0) {
            $io->success("✅ {$cleanedCount} article(s) nettoyé(s) avec succès !");
            $io->note('Les balises <mark> ont été remplacées par <strong class="highlight">.');
        } else {
            $io->info('Aucun article à nettoyer. Tous les contenus sont déjà conformes.');
        }

        return Command::SUCCESS;
    }

    private function cleanContent(string $content): string
    {
        // Remplacer les balises <mark> par <strong class="highlight">
        $content = preg_replace('/<mark([^>]*)>/i', '<strong class="highlight"$1>', $content);
        $content = preg_replace('/<\/mark>/i', '</strong>', $content);

        // Autres nettoyages si nécessaire
        $content = preg_replace('/<font([^>]*)>/i', '<span$1>', $content);
        $content = preg_replace('/<\/font>/i', '</span>', $content);

        // Supprimer les balises center obsolètes
        $content = preg_replace('/<center([^>]*)>/i', '<div style="text-align: center"$1>', $content);
        $content = preg_replace('/<\/center>/i', '</div>', $content);

        return $content;
    }
}