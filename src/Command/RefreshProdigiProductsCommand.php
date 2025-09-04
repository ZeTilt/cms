<?php

namespace App\Command;

use App\Service\ProdigiProductCacheService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'prodigi:refresh-products',
    description: 'Rafraîchit le cache des produits Prodigi en arrière-plan'
)]
class RefreshProdigiProductsCommand extends Command
{
    public function __construct(
        private ProdigiProductCacheService $cacheService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force-all', 'f', InputOption::VALUE_NONE, 'Force la synchronisation de TOUS les SKUs')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limite le nombre de produits à rafraîchir', 10)
            ->setHelp('Cette commande rafraîchit le cache des produits Prodigi de manière optimisée.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');
        $forceAll = $input->getOption('force-all');

        $io->title('🔄 Refresh Cache Prodigi');

        try {
            if ($forceAll) {
                $io->info('🚀 Synchronisation COMPLÈTE forcée (tous les SKUs)...');
                $result = $this->cacheService->forceSyncAllProducts();
                
                if ($result['success']) {
                    $io->success(sprintf(
                        '✅ Synchronisation complète réussie: %d produits mis à jour',
                        $result['formats_count']
                    ));
                } else {
                    $io->error('❌ Échec de la synchronisation complète');
                    return Command::FAILURE;
                }
            } else {
                $io->info("🔄 Refresh intelligent (max {$limit} produits)...");
                
                // Utiliser la méthode de refresh normale mais forcer les appels API
                $result = $this->cacheService->refreshOldestProducts($limit);
                
                $io->success(sprintf(
                    '✅ Refresh intelligent terminé: %d produits mis à jour',
                    $result['refreshed_count'] ?? 0
                ));
            }

            // Afficher les statistiques
            $stats = $this->cacheService->getCacheStats();
            
            $io->section('📊 Statistiques du cache');
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Total produits', $stats['total_products']],
                    ['Produits disponibles', $stats['available_products']],
                    ['Produits récents (<24h)', $stats['recent_products']],
                    ['Produits à rafraîchir (>24h)', $stats['old_products']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('❌ Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}