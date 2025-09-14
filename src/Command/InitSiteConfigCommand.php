<?php

namespace App\Command;

use App\Service\SiteConfigService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-site-config',
    description: 'Initialize site configuration with default values',
)]
class InitSiteConfigCommand extends Command
{
    public function __construct(
        private SiteConfigService $siteConfigService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $defaultConfigs = [
            'club_name' => 'Club Subaquatique des Vénètes',
            'club_address' => '5 Av. du Président Wilson, 56000 Vannes',
            'club_phone' => '02 97 XX XX XX',
            'club_email' => 'contact@plongee-venetes.fr',
            'club_facebook' => 'https://www.facebook.com/plongeevenetes/'
        ];

        foreach ($defaultConfigs as $key => $value) {
            // Only set if not already configured
            if (!$this->siteConfigService->get($key)) {
                $this->siteConfigService->set($key, $value);
                $io->success("Set {$key}: {$value}");
            } else {
                $io->note("Configuration {$key} already exists, skipping.");
            }
        }

        $io->success('Site configuration initialized successfully.');

        return Command::SUCCESS;
    }
}