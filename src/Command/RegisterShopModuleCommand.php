<?php

namespace App\Command;

use App\Service\ModuleManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:register-shop-module',
    description: 'Register the Shop module in the system',
)]
class RegisterShopModuleCommand extends Command
{
    public function __construct(
        private ModuleManager $moduleManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $module = $this->moduleManager->registerModule(
                'shop',
                'Boutique & Paiement',
                'Module de vente d\'accès aux galeries avec paiement via MangoPay',
                [
                    'dependencies' => ['gallery'],
                    'default_currency' => 'EUR',
                    'mangopay_enabled' => false
                ]
            );

            $io->success("Module Shop enregistré avec succès ! ID: {$module->getId()}");
            $io->note("Le module est désactivé par défaut. Activez-le depuis l'interface d'administration des modules.");
            $io->warning("ATTENTION: Le module Shop nécessite que le module Gallery soit activé.");

        } catch (\Exception $e) {
            $io->error("Erreur lors de l'enregistrement du module: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}