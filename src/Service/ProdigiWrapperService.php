<?php

namespace App\Service;

use Wingly\Prodigi\Prodigi;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * Service wrapper pour l'API Prodigi utilisant le package Wingly
 * Permet de basculer entre API réelle et données de fallback
 */
class ProdigiWrapperService
{
    private ?Prodigi $prodigiClient = null;
    private bool $apiEnabled = false;

    public function __construct(
        private string $prodigiApiKey,
        private bool $prodigiSandbox = true,
        private LoggerInterface $logger
    ) {
        $this->initializeClient();
    }

    /**
     * Initialiser le client Prodigi
     */
    private function initializeClient(): void
    {
        try {
            $guzzleClient = new Client();
            $this->prodigiClient = new Prodigi($guzzleClient);
            $this->prodigiClient->setApiKey($this->prodigiApiKey);
            $this->prodigiClient->setApiUrl($this->prodigiSandbox ? 'sandbox' : 'production');
            
            // Test de connectivité simple (sans créer de commande)
            $this->testConnection();
            
        } catch (\Exception $e) {
            $this->logger->warning('Prodigi API client initialization failed', [
                'error' => $e->getMessage(),
                'fallback' => 'Using fallback data'
            ]);
            $this->apiEnabled = false;
        }
    }

    /**
     * Tester la connexion à l'API (sans créer de données)
     */
    private function testConnection(): void
    {
        // Pour l'instant, on considère que l'API n'est pas accessible
        // jusqu'à ce qu'on ait une clé API valide
        $this->apiEnabled = false;
        
        // TODO: Activer quand l'API key sera validée
        // try {
        //     // Test simple : récupérer les commandes existantes
        //     $orders = $this->prodigiClient->getOrders();
        //     $this->apiEnabled = true;
        // } catch (\Exception $e) {
        //     $this->apiEnabled = false;
        // }
    }

    /**
     * Vérifier si l'API est disponible
     */
    public function isApiEnabled(): bool
    {
        return $this->apiEnabled;
    }

    /**
     * Créer une commande via l'API Prodigi
     */
    public function createOrder(array $orderData): array
    {
        if (!$this->apiEnabled || !$this->prodigiClient) {
            throw new \Exception('API Prodigi non disponible');
        }

        try {
            return $this->prodigiClient->createOrder($orderData);
        } catch (\Exception $e) {
            $this->logger->error('Erreur création commande Prodigi', [
                'error' => $e->getMessage(),
                'data' => $orderData
            ]);
            throw $e;
        }
    }

    /**
     * Récupérer une commande par ID
     */
    public function getOrder(string $orderId): array
    {
        if (!$this->apiEnabled || !$this->prodigiClient) {
            throw new \Exception('API Prodigi non disponible');
        }

        try {
            return $this->prodigiClient->getOrder($orderId);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération commande Prodigi', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            throw $e;
        }
    }

    /**
     * Annuler une commande
     */
    public function cancelOrder(string $orderId): array
    {
        if (!$this->apiEnabled || !$this->prodigiClient) {
            throw new \Exception('API Prodigi non disponible');
        }

        try {
            return $this->prodigiClient->cancelOrder($orderId);
        } catch (\Exception $e) {
            $this->logger->error('Erreur annulation commande Prodigi', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            throw $e;
        }
    }

    /**
     * Obtenir les actions disponibles pour une commande
     */
    public function getAvailableActions(string $orderId): array
    {
        if (!$this->apiEnabled || !$this->prodigiClient) {
            throw new \Exception('API Prodigi non disponible');
        }

        try {
            return $this->prodigiClient->getAvailableActions($orderId);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération actions commande Prodigi', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            throw $e;
        }
    }

    /**
     * Activer manuellement l'API (pour les tests)
     */
    public function enableApi(): void
    {
        $this->apiEnabled = true;
    }

    /**
     * Désactiver l'API
     */
    public function disableApi(): void
    {
        $this->apiEnabled = false;
    }

    /**
     * Obtenir le statut de l'API
     */
    public function getApiStatus(): array
    {
        return [
            'api_enabled' => $this->apiEnabled,
            'client_initialized' => $this->prodigiClient !== null,
            'environment' => $this->prodigiSandbox ? 'sandbox' : 'production',
            'api_key_configured' => !empty($this->prodigiApiKey),
            'last_check' => new \DateTime()
        ];
    }
}