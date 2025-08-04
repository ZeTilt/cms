<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CeweApiService
{
    private const API_BASE_URL = 'https://cmp.photoprintit.com/api/2.0';
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $ceweApiKey,
        private string $cewePartnerId
    ) {}

    /**
     * Obtenir la liste des produits disponibles
     */
    public function getAvailableProducts(): array
    {
        $response = $this->makeRequest('GET', '/products');
        return $this->handleResponse($response);
    }

    /**
     * Obtenir les tarifs pour un format donné
     */
    public function getPricing(string $format, string $paperType = 'standard'): array
    {
        $response = $this->makeRequest('GET', '/pricing', [
            'format' => $format,
            'paper_type' => $paperType
        ]);
        return $this->handleResponse($response);
    }

    /**
     * Créer une commande CEWE
     */
    public function createOrder(array $orderData): array
    {
        $response = $this->makeRequest('POST', '/orders', $orderData);
        return $this->handleResponse($response);
    }

    /**
     * Obtenir le statut d'une commande
     */
    public function getOrderStatus(string $ceweOrderId): array
    {
        $response = $this->makeRequest('GET', "/orders/{$ceweOrderId}");
        return $this->handleResponse($response);
    }

    /**
     * Uploader une image vers CEWE
     */
    public function uploadImage(string $imagePath, array $metadata = []): array
    {
        $response = $this->makeRequest('POST', '/images/upload', [
            'image' => new \CURLFile($imagePath),
            'metadata' => json_encode($metadata)
        ]);
        return $this->handleResponse($response);
    }

    /**
     * Calculer le prix total d'une commande
     */
    public function calculateOrderTotal(array $items): float
    {
        $total = 0.0;
        
        foreach ($items as $item) {
            $pricing = $this->getPricing($item['format'], $item['paper_type']);
            $unitPrice = $pricing['unit_price'] ?? 0.0;
            $total += $unitPrice * $item['quantity'];
        }
        
        return $total;
    }

    /**
     * Formater les données de commande pour l'API CEWE
     */
    public function formatOrderData(array $orderItems, array $shippingAddress, string $customerEmail): array
    {
        return [
            'partner_id' => $this->cewePartnerId,
            'customer' => [
                'email' => $customerEmail,
                'shipping_address' => $shippingAddress
            ],
            'items' => array_map(function ($item) {
                return [
                    'image_id' => $item['image_id'],
                    'format' => $item['format'],
                    'paper_type' => $item['paper_type'],
                    'quantity' => $item['quantity'],
                    'crop_settings' => $item['crop_settings'] ?? []
                ];
            }, $orderItems),
            'delivery_options' => [
                'speed' => 'standard', // standard, express
                'tracking' => true
            ]
        ];
    }

    /**
     * Obtenir les formats de tirage disponibles
     */
    public function getAvailableFormats(): array
    {
        // Configuration statique des formats courants
        // En production, ceci devrait venir de l'API CEWE
        return [
            '10x15' => [
                'name' => '10x15 cm',
                'description' => 'Format photo standard',
                'base_price' => 0.19
            ],
            '13x18' => [
                'name' => '13x18 cm',
                'description' => 'Format photo moyen',
                'base_price' => 0.29
            ],
            '15x21' => [
                'name' => '15x21 cm (A5)',
                'description' => 'Format A5',
                'base_price' => 0.49
            ],
            '20x30' => [
                'name' => '20x30 cm',
                'description' => 'Format poster petit',
                'base_price' => 1.99
            ],
            '30x40' => [
                'name' => '30x40 cm',
                'description' => 'Format poster moyen',
                'base_price' => 4.99
            ]
        ];
    }

    /**
     * Obtenir les types de papier disponibles
     */
    public function getAvailablePaperTypes(): array
    {
        return [
            'standard' => [
                'name' => 'Papier Standard',
                'description' => 'Papier photo mat standard',
                'price_multiplier' => 1.0
            ],
            'premium' => [
                'name' => 'Papier Premium',
                'description' => 'Papier photo brillant premium',
                'price_multiplier' => 1.5
            ],
            'canvas' => [
                'name' => 'Toile Canvas',
                'description' => 'Impression sur toile canvas',
                'price_multiplier' => 3.0
            ]
        ];
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): ResponseInterface
    {
        $url = self::API_BASE_URL . $endpoint;
        
        $options = [
            'headers' => [
                'apiAccessKey' => $this->ceweApiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];

        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        } elseif ($method !== 'GET') {
            $options['json'] = $data;
        }

        return $this->httpClient->request($method, $url, $options);
    }

    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);

        if ($statusCode >= 400) {
            throw new \Exception("CEWE API Error (HTTP {$statusCode}): " . $content);
        }

        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from CEWE API: ' . json_last_error_msg());
        }

        return $data;
    }
}