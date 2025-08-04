<?php

namespace App\Service;

use App\Service\MangoPayService;
use Psr\Log\LoggerInterface;

class WebhookConfigurationService
{
    public function __construct(
        private MangoPayService $mangoPayService,
        private LoggerInterface $logger,
        private string $webhookUrl
    ) {}

    /**
     * Configurer les webhooks MangoPay
     */
    public function configureMangoPay(): bool
    {
        try {
            $this->logger->info('Configuring MangoPay webhooks');

            // Liste des événements à surveiller
            $eventTypes = [
                'PAYIN_NORMAL_SUCCEEDED',
                'PAYIN_NORMAL_FAILED',
                'TRANSFER_NORMAL_SUCCEEDED', 
                'TRANSFER_NORMAL_FAILED',
                'PAYOUT_NORMAL_SUCCEEDED',
                'PAYOUT_NORMAL_FAILED',
                'KYC_SUCCEEDED',
                'KYC_FAILED',
                'UBO_DECLARATION_VALIDATED',
                'UBO_DECLARATION_REFUSED'
            ];

            // URL du webhook
            $webhookUrl = $this->webhookUrl . '/webhooks/mangopay';

            foreach ($eventTypes as $eventType) {
                $this->createOrUpdateWebhook($eventType, $webhookUrl);
            }

            $this->logger->info('MangoPay webhooks configured successfully');
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to configure MangoPay webhooks: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Créer ou mettre à jour un webhook pour un type d'événement
     */
    private function createOrUpdateWebhook(string $eventType, string $url): void
    {
        try {
            // Vérifier si le webhook existe déjà
            $existingHooks = $this->mangoPayService->listWebhooks();
            
            $existingHook = null;
            foreach ($existingHooks as $hook) {
                if ($hook->EventType === $eventType && $hook->Url === $url) {
                    $existingHook = $hook;
                    break;
                }
            }

            if ($existingHook) {
                // Mettre à jour le webhook existant s'il est inactif
                if ($existingHook->Status !== 'ENABLED') {
                    $this->mangoPayService->updateWebhook($existingHook->Id, [
                        'Url' => $url,
                        'Status' => 'ENABLED'
                    ]);
                    $this->logger->info("Updated webhook for event type: {$eventType}");
                } else {
                    $this->logger->info("Webhook already exists and is enabled for event type: {$eventType}");
                }
            } else {
                // Créer un nouveau webhook
                $webhook = $this->mangoPayService->createWebhook([
                    'EventType' => $eventType,
                    'Url' => $url
                ]);
                $this->logger->info("Created webhook for event type: {$eventType}", ['webhook_id' => $webhook->Id]);
            }

        } catch (\Exception $e) {
            $this->logger->error("Failed to configure webhook for event type {$eventType}: " . $e->getMessage());
        }
    }

    /**
     * Lister tous les webhooks configurés
     */
    public function listConfiguredWebhooks(): array
    {
        try {
            return $this->mangoPayService->listWebhooks();
        } catch (\Exception $e) {
            $this->logger->error('Failed to list webhooks: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Désactiver tous les webhooks
     */
    public function disableAllWebhooks(): bool
    {
        try {
            $webhooks = $this->listConfiguredWebhooks();
            
            foreach ($webhooks as $webhook) {
                if ($webhook->Status === 'ENABLED') {
                    $this->mangoPayService->updateWebhook($webhook->Id, [
                        'Status' => 'DISABLED'
                    ]);
                    $this->logger->info("Disabled webhook: {$webhook->Id}");
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to disable webhooks: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Tester la connectivité du webhook
     */
    public function testWebhookConnectivity(): array
    {
        $results = [];
        
        try {
            // Test simple de l'endpoint
            $testUrl = $this->webhookUrl . '/webhooks/test';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $results['test_endpoint'] = [
                'url' => $testUrl,
                'status_code' => $httpCode,
                'response' => $response,
                'success' => $httpCode === 200
            ];
            
        } catch (\Exception $e) {
            $results['test_endpoint'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        return $results;
    }
}