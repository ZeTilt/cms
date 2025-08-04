<?php

namespace App\Command;

use App\Service\WebhookConfigurationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:webhooks:configure',
    description: 'Configure webhooks for payment providers'
)]
class ConfigureWebhooksCommand extends Command
{
    public function __construct(
        private WebhookConfigurationService $webhookService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('provider', 'p', InputOption::VALUE_OPTIONAL, 'Payment provider (mangopay)', 'mangopay')
            ->addOption('test', 't', InputOption::VALUE_NONE, 'Test webhook connectivity')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List configured webhooks')
            ->addOption('disable', 'd', InputOption::VALUE_NONE, 'Disable all webhooks')
            ->setHelp('This command helps you configure webhooks for payment notifications.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $provider = $input->getOption('provider');

        $io->title('ZeTilt CMS - Webhook Configuration');

        // Test connectivity
        if ($input->getOption('test')) {
            return $this->testWebhooks($io);
        }

        // List webhooks
        if ($input->getOption('list')) {
            return $this->listWebhooks($io, $provider);
        }

        // Disable webhooks
        if ($input->getOption('disable')) {
            return $this->disableWebhooks($io, $provider);
        }

        // Configure webhooks
        return $this->configureWebhooks($io, $provider);
    }

    private function configureWebhooks(SymfonyStyle $io, string $provider): int
    {
        $io->section("Configuring webhooks for: {$provider}");

        switch ($provider) {
            case 'mangopay':
                $io->text('Configuring MangoPay webhooks...');
                
                if ($this->webhookService->configureMangoPay()) {
                    $io->success('MangoPay webhooks configured successfully!');
                    
                    // List configured webhooks
                    $this->listWebhooks($io, $provider);
                    
                    return Command::SUCCESS;
                } else {
                    $io->error('Failed to configure MangoPay webhooks. Check the logs for details.');
                    return Command::FAILURE;
                }

            default:
                $io->error("Unsupported provider: {$provider}");
                return Command::FAILURE;
        }
    }

    private function listWebhooks(SymfonyStyle $io, string $provider): int
    {
        $io->section("Listing configured webhooks for: {$provider}");

        try {
            $webhooks = $this->webhookService->listConfiguredWebhooks();
            
            if (empty($webhooks)) {
                $io->warning('No webhooks configured.');
                return Command::SUCCESS;
            }

            $table = [];
            foreach ($webhooks as $webhook) {
                $table[] = [
                    $webhook->Id ?? 'N/A',
                    $webhook->EventType ?? 'N/A',
                    $webhook->Url ?? 'N/A',
                    $webhook->Status ?? 'N/A',
                    $webhook->CreationDate ?? 'N/A'
                ];
            }

            $io->table(
                ['ID', 'Event Type', 'URL', 'Status', 'Created'],
                $table
            );

            $io->success(sprintf('Found %d configured webhook(s).', count($webhooks)));
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to list webhooks: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function disableWebhooks(SymfonyStyle $io, string $provider): int
    {
        $io->section("Disabling webhooks for: {$provider}");

        if (!$io->confirm('Are you sure you want to disable all webhooks? This will stop payment notifications.')) {
            $io->note('Operation cancelled.');
            return Command::SUCCESS;
        }

        if ($this->webhookService->disableAllWebhooks()) {
            $io->success('All webhooks have been disabled.');
            return Command::SUCCESS;
        } else {
            $io->error('Failed to disable webhooks. Check the logs for details.');
            return Command::FAILURE;
        }
    }

    private function testWebhooks(SymfonyStyle $io): int
    {
        $io->section('Testing webhook connectivity');

        $results = $this->webhookService->testWebhookConnectivity();

        foreach ($results as $test => $result) {
            if ($result['success']) {
                $io->success("✅ {$test}: OK");
                if (isset($result['response'])) {
                    $io->text("   Response: {$result['response']}");
                }
            } else {
                $io->error("❌ {$test}: FAILED");
                if (isset($result['error'])) {
                    $io->text("   Error: {$result['error']}");
                }
                if (isset($result['status_code'])) {
                    $io->text("   Status Code: {$result['status_code']}");
                }
            }
        }

        return Command::SUCCESS;
    }
}