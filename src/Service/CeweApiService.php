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
        // Configuration complète des produits CEWE
        // Basée sur le catalogue cewe.fr 2024
        return [
            // TIRAGES PHOTO CLASSIQUES
            '9x13' => [
                'name' => '9x13 cm',
                'description' => 'Format photo standard',
                'category' => 'tirages',
                'base_price' => 0.15
            ],
            '10x15' => [
                'name' => '10x15 cm',
                'description' => 'Format photo populaire',
                'category' => 'tirages',
                'base_price' => 0.19
            ],
            '11x15' => [
                'name' => '11x15 cm',
                'description' => 'Format carré étendu',
                'category' => 'tirages',
                'base_price' => 0.25
            ],
            '13x18' => [
                'name' => '13x18 cm',
                'description' => 'Format photo moyen',
                'category' => 'tirages',
                'base_price' => 0.29
            ],
            '15x21' => [
                'name' => '15x21 cm (A5)',
                'description' => 'Format A5',
                'category' => 'tirages',
                'base_price' => 0.49
            ],
            '20x30' => [
                'name' => '20x30 cm',
                'description' => 'Format poster',
                'category' => 'tirages',
                'base_price' => 1.99
            ],
            
            // TIRAGES GRAND FORMAT
            '30x40' => [
                'name' => '30x40 cm',
                'description' => 'Poster moyen format',
                'category' => 'grands_formats',
                'base_price' => 4.99
            ],
            '30x45' => [
                'name' => '30x45 cm',
                'description' => 'Poster panoramique',
                'category' => 'grands_formats',
                'base_price' => 5.99
            ],
            '40x60' => [
                'name' => '40x60 cm',
                'description' => 'Grand poster',
                'category' => 'grands_formats',
                'base_price' => 9.99
            ],
            '50x75' => [
                'name' => '50x75 cm',
                'description' => 'Très grand format',
                'category' => 'grands_formats',
                'base_price' => 14.99
            ],
            
            // PUZZLES PHOTO
            'puzzle_120' => [
                'name' => 'Puzzle 120 pièces',
                'description' => 'Puzzle photo personnalisé',
                'category' => 'puzzles',
                'base_price' => 19.99
            ],
            'puzzle_252' => [
                'name' => 'Puzzle 252 pièces',
                'description' => 'Puzzle photo moyen',
                'category' => 'puzzles',
                'base_price' => 24.99
            ],
            'puzzle_500' => [
                'name' => 'Puzzle 500 pièces',
                'description' => 'Puzzle photo standard',
                'category' => 'puzzles',
                'base_price' => 29.99
            ],
            'puzzle_1000' => [
                'name' => 'Puzzle 1000 pièces',
                'description' => 'Grand puzzle photo',
                'category' => 'puzzles',
                'base_price' => 34.99
            ],
            
            // CADEAUX PERSONNALISÉS
            'mug_standard' => [
                'name' => 'Mug Photo',
                'description' => 'Mug blanc personnalisé',
                'category' => 'cadeaux',
                'base_price' => 12.99
            ],
            'mug_magique' => [
                'name' => 'Mug Magique',
                'description' => 'Mug qui change de couleur',
                'category' => 'cadeaux',
                'base_price' => 16.99
            ],
            'coussin_40x40' => [
                'name' => 'Coussin 40x40 cm',
                'description' => 'Coussin photo personnalisé',
                'category' => 'cadeaux',
                'base_price' => 24.99
            ],
            'coussin_35x35' => [
                'name' => 'Coussin 35x35 cm',
                'description' => 'Petit coussin photo',
                'category' => 'cadeaux',
                'base_price' => 21.99
            ],
            'tshirt_homme' => [
                'name' => 'T-shirt Homme',
                'description' => 'T-shirt photo personnalisé',
                'category' => 'cadeaux',
                'base_price' => 19.99
            ],
            'tshirt_femme' => [
                'name' => 'T-shirt Femme',
                'description' => 'T-shirt photo personnalisé',
                'category' => 'cadeaux',
                'base_price' => 19.99
            ],
            'tote_bag' => [
                'name' => 'Tote Bag',
                'description' => 'Sac en toile personnalisé',
                'category' => 'cadeaux',
                'base_price' => 14.99
            ],
            
            // DÉCORATION
            'tableau_alu' => [
                'name' => 'Tableau Alu-Dibond',
                'description' => 'Photo sur aluminium',
                'category' => 'decoration',
                'base_price' => 24.99
            ],
            'tableau_plexi' => [
                'name' => 'Tableau Plexiglas',
                'description' => 'Photo sur plexiglas',
                'category' => 'decoration',
                'base_price' => 29.99
            ],
            'toile_chassis' => [
                'name' => 'Toile sur Châssis',
                'description' => 'Impression sur toile tendue',
                'category' => 'decoration',
                'base_price' => 19.99
            ],
            'cadre_bois' => [
                'name' => 'Cadre Bois',
                'description' => 'Photo avec cadre en bois',
                'category' => 'decoration',
                'base_price' => 15.99
            ],
            'cadre_metal' => [
                'name' => 'Cadre Métal',
                'description' => 'Photo avec cadre métallique',
                'category' => 'decoration',
                'base_price' => 18.99
            ],
            
            // CALENDRIERS
            'calendrier_mural' => [
                'name' => 'Calendrier Mural A4',
                'description' => 'Calendrier photo 12 mois',
                'category' => 'calendriers',
                'base_price' => 14.99
            ],
            'calendrier_bureau' => [
                'name' => 'Calendrier de Bureau',
                'description' => 'Petit calendrier bureau',
                'category' => 'calendriers',
                'base_price' => 9.99
            ],
            'calendrier_chevalet' => [
                'name' => 'Calendrier Chevalet',
                'description' => 'Calendrier sur chevalet',
                'category' => 'calendriers',
                'base_price' => 12.99
            ],
            
            // LIVRES PHOTO
            'livre_photo_a4' => [
                'name' => 'Livre Photo A4',
                'description' => 'Livre photo couverture souple',
                'category' => 'livres',
                'base_price' => 19.99
            ],
            'livre_photo_a5' => [
                'name' => 'Livre Photo A5',
                'description' => 'Petit livre photo',
                'category' => 'livres',
                'base_price' => 14.99
            ],
            'livre_premium' => [
                'name' => 'Livre Photo Premium',
                'description' => 'Livre photo couverture rigide',
                'category' => 'livres',
                'base_price' => 29.99
            ],
            
            // CARTES & FAIRE-PART
            'carte_simple' => [
                'name' => 'Carte Simple',
                'description' => 'Carte photo 10x15',
                'category' => 'cartes',
                'base_price' => 2.99
            ],
            'carte_double' => [
                'name' => 'Carte Double',
                'description' => 'Carte photo pliée',
                'category' => 'cartes',
                'base_price' => 3.99
            ],
            'faire_part' => [
                'name' => 'Faire-part',
                'description' => 'Faire-part personnalisé',
                'category' => 'cartes',
                'base_price' => 4.99
            ],
            'invitations' => [
                'name' => 'Invitations',
                'description' => 'Cartes d\'invitation photo',
                'category' => 'cartes',
                'base_price' => 3.49
            ]
        ];
    }

    /**
     * Obtenir les formats groupés par catégorie
     */
    public function getFormatsByCategory(): array
    {
        $formats = $this->getAvailableFormats();
        $grouped = [];
        
        foreach ($formats as $key => $format) {
            $category = $format['category'] ?? 'autres';
            $grouped[$category][$key] = $format;
        }
        
        return $grouped;
    }
    
    /**
     * Obtenir les catégories de produits disponibles
     */
    public function getProductCategories(): array
    {
        return [
            'tirages' => [
                'name' => 'Tirages Photo',
                'description' => 'Tirages photo classiques'
            ],
            'grands_formats' => [
                'name' => 'Grands Formats',
                'description' => 'Posters et grands tirages'
            ],
            'puzzles' => [
                'name' => 'Puzzles Photo',
                'description' => 'Puzzles personnalisés'
            ],
            'cadeaux' => [
                'name' => 'Cadeaux Personnalisés',
                'description' => 'Mugs, coussins, t-shirts...'
            ],
            'decoration' => [
                'name' => 'Décoration',
                'description' => 'Tableaux, cadres, toiles...'
            ],
            'calendriers' => [
                'name' => 'Calendriers',
                'description' => 'Calendriers personnalisés'
            ],
            'livres' => [
                'name' => 'Livres Photo',
                'description' => 'Albums et livres photo'
            ],
            'cartes' => [
                'name' => 'Cartes & Faire-part',
                'description' => 'Cartes et invitations'
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