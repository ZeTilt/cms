<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Psr\Log\LoggerInterface;

class ProdigiApiService
{
    private const API_SANDBOX_URL = 'https://api.sandbox.prodigi.com';
    private const API_LIVE_URL = 'https://api.prodigi.com';
    
    private ?string $runtimeApiKey = null;
    private bool $useSandbox = true;
    private ?ProdigiWrapperService $wrapperService = null;
    private bool $useApiWrapper = false;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $prodigiApiKey,
        private bool $prodigiSandbox = true,
        private ?LoggerInterface $logger = null
    ) {
        $this->useSandbox = $prodigiSandbox;
        $this->initializeWrapperService();
    }
    
    /**
     * Initialiser le service wrapper Prodigi
     */
    private function initializeWrapperService(): void
    {
        if ($this->logger) {
            try {
                $this->wrapperService = new ProdigiWrapperService(
                    $this->prodigiApiKey,
                    $this->prodigiSandbox,
                    $this->logger
                );
                
                // Vérifier si l'API wrapper est disponible
                $this->useApiWrapper = $this->wrapperService->isApiEnabled();
            } catch (\Exception $e) {
                $this->logger?->warning('Wrapper Prodigi non disponible, utilisation du fallback', [
                    'error' => $e->getMessage()
                ]);
                $this->useApiWrapper = false;
            }
        }
    }
    
    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }
    
    /**
     * Set temporary API credentials (for testing)
     */
    public function setRuntimeCredentials(string $apiKey, bool $sandbox = true): void
    {
        $this->runtimeApiKey = $apiKey;
        $this->useSandbox = $sandbox;
    }
    
    /**
     * Get the API key (runtime or default)
     */
    private function getApiKey(): string
    {
        return $this->runtimeApiKey ?: $this->prodigiApiKey;
    }
    
    /**
     * Get the base URL based on environment
     */
    private function getBaseUrl(): string
    {
        return $this->useSandbox ? self::API_SANDBOX_URL : self::API_LIVE_URL;
    }

    /**
     * Obtenir la liste des produits disponibles depuis Prodigi
     */
    public function getAvailableProducts(): array
    {
        $response = $this->makeRequest('GET', '/v4.0/products');
        return $this->handleResponse($response);
    }

    /**
     * Obtenir un devis pour des produits donnés
     */
    public function createQuote(array $items): array
    {
        $quoteData = [
            'items' => $items,
            'shippingAddress' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'address1' => '123 Test Street',
                'city' => 'Paris',
                'postcode' => '75001',
                'countryCode' => 'FR'
            ]
        ];
        
        $response = $this->makeRequest('POST', '/v4.0/quotes', $quoteData);
        return $this->handleResponse($response);
    }

    /**
     * Créer une commande Prodigi
     */
    public function createOrder(array $orderData): array
    {
        $response = $this->makeRequest('POST', '/v4.0/orders', $orderData);
        return $this->handleResponse($response);
    }

    /**
     * Obtenir le statut d'une commande
     */
    public function getOrderStatus(string $orderId): array
    {
        $response = $this->makeRequest('GET', "/v4.0/orders/{$orderId}");
        return $this->handleResponse($response);
    }
    
    /**
     * Obtenir toutes les commandes
     */
    public function getOrders(array $filters = []): array
    {
        $response = $this->makeRequest('GET', '/v4.0/orders', $filters);
        return $this->handleResponse($response);
    }

    /**
     * Obtenir les détails d'un produit par SKU
     */
    public function getProduct(string $sku): array
    {
        $response = $this->makeRequest('GET', "/v4.0/products/{$sku}");
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
     * Formater les données de commande pour l'API Prodigi
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
     * Obtenir les formats de tirage disponibles depuis l'API Prodigi
     */
    public function getAvailableFormats(): array
    {
        try {
            // TOUJOURS utiliser l'API de PRODUCTION pour récupérer les produits
            // même si l'environnement général est en sandbox
            $productionService = $this->createProductionService();
            $productService = new ProdigiProductService($productionService, $this->logger);
            $result = $productService->getAllProducts();
            
            $formats = [];
            foreach ($result['products'] as $sku => $product) {
                $formats[$sku] = [
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'base_price' => $product['base_price'],
                    'category' => $product['category'],
                    'dimensions' => $product['dimensions'],
                    'paper_type' => $product['paper_type'],
                    'sku' => $sku
                ];
            }
            
            $this->logger?->info('Produits Prodigi récupérés depuis PRODUCTION', [
                'total_products' => count($formats),
                'successful' => $result['successful'],
                'attempted' => $result['total_attempted'],
                'environment' => 'production'
            ]);
            
            return $formats;
            
        } catch (\Exception $e) {
            $this->logger?->error('Erreur récupération produits Prodigi', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Créer une instance du service API toujours en mode PRODUCTION pour les produits
     */
    private function createProductionService(): self
    {
        // Créer une nouvelle instance forcée en production pour les produits
        return new self(
            $this->httpClient,
            $this->getApiKey(), // Utilise la clé API actuelle
            false, // TOUJOURS false pour les produits (production)
            $this->logger
        );
    }
    
    /**
     * Formater les produits depuis la réponse API Prodigi
     */
    private function formatProductsFromProdigiApi(array $products): array
    {
        $formattedProducts = [];
        
        foreach ($products as $product) {
            $productId = $product['id'] ?? $product['sku'] ?? uniqid();
            $formattedProducts[$productId] = [
                'name' => $product['name'] ?? 'Produit Prodigi',
                'description' => $product['description'] ?? '',
                'category' => $this->determineCategoryFromProdigiProduct($product),
                'base_price' => $this->extractPriceFromProdigiProduct($product),
                'sku' => $product['sku'] ?? $productId,
                'variants' => $product['variants'] ?? [],
                'api_data' => $product // Conserver les données originales
            ];
        }
        
        return $formattedProducts;
    }
    
    /**
     * Extraire le prix d'un produit Prodigi
     */
    private function extractPriceFromProdigiProduct(array $product): float
    {
        // Prodigi a une structure de prix différente
        if (isset($product['variants']) && !empty($product['variants'])) {
            $firstVariant = $product['variants'][0];
            if (isset($firstVariant['cost'])) {
                return (float) $firstVariant['cost'];
            }
        }
        return 0.0;
    }
    
    /**
     * Déterminer la catégorie d'un produit depuis les données API Prodigi
     */
    private function determineCategoryFromProdigiProduct(array $product): string
    {
        $name = strtolower($product['name'] ?? '');
        
        // Mapping basé sur les mots-clés Prodigi
        if (strpos($name, 'print') !== false || strpos($name, 'photo') !== false) {
            return 'tirages';
        }
        
        if (strpos($name, 'poster') !== false || strpos($name, 'canvas') !== false) {
            return 'grands_formats';
        }
        
        if (strpos($name, 'mug') !== false || strpos($name, 'cup') !== false) {
            return 'cadeaux';
        }
        
        if (strpos($name, 'tshirt') !== false || strpos($name, 't-shirt') !== false || strpos($name, 'shirt') !== false) {
            return 'cadeaux';
        }
        
        if (strpos($name, 'pillow') !== false || strpos($name, 'cushion') !== false) {
            return 'cadeaux';
        }
        
        if (strpos($name, 'book') !== false || strpos($name, 'album') !== false) {
            return 'livres';
        }
        
        if (strpos($name, 'card') !== false || strpos($name, 'postcard') !== false) {
            return 'cartes';
        }
        
        if (strpos($name, 'frame') !== false || strpos($name, 'metal') !== false || strpos($name, 'acrylic') !== false) {
            return 'decoration';
        }
        
        // Par défaut, considérer comme tirage
        return 'tirages';
    }
    
    /**
     * Données de fallback si l'API Prodigi n'est pas disponible
     */
    private function getFallbackFormats(): array
    {
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
            ],
            'autres' => [
                'name' => 'Autres Produits',
                'description' => 'Produits divers et spécialisés'
            ]
        ];
    }

    /**
     * Obtenir les types de papier disponibles depuis l'API Prodigi
     */
    public function getAvailablePaperTypes(): array
    {
        try {
            // Extraire les types de papier depuis les produits récupérés
            $formats = $this->getAvailableFormats();
            $paperTypes = [];
            
            foreach ($formats as $format) {
                $paperType = $format['paper_type'] ?? 'Standard';
                $typeKey = strtolower(str_replace([' ', '-'], '_', $paperType));
                
                if (!isset($paperTypes[$typeKey])) {
                    // Définir le multiplicateur de prix selon le type
                    $multiplier = 1.0;
                    if (strpos($paperType, 'Canvas') !== false) {
                        $multiplier = 3.0;
                    } elseif (strpos($paperType, 'Fine Art') !== false) {
                        $multiplier = 2.0;
                    } elseif (strpos($paperType, 'Photo') !== false) {
                        $multiplier = 1.5;
                    }
                    
                    $paperTypes[$typeKey] = [
                        'name' => $paperType,
                        'description' => "Papier {$paperType}",
                        'price_multiplier' => $multiplier,
                        'available' => true
                    ];
                }
            }
            
            $this->logger?->info('Types de papier extraits des produits Prodigi', [
                'paper_types_count' => count($paperTypes)
            ]);
            
            return $paperTypes;
            
        } catch (\Exception $e) {
            $this->logger?->error('Erreur extraction types de papier', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Formater les types de papier depuis la réponse API Prodigi
     */
    private function formatPaperTypesFromApi(array $paperTypes): array
    {
        $formattedTypes = [];
        
        foreach ($paperTypes as $paperType) {
            $typeId = $paperType['id'] ?? $paperType['code'] ?? uniqid();
            $formattedTypes[$typeId] = [
                'name' => $paperType['name'] ?? $paperType['title'] ?? 'Type de papier',
                'description' => $paperType['description'] ?? '',
                'price_multiplier' => (float) ($paperType['price_multiplier'] ?? $paperType['multiplier'] ?? 1.0),
                'finish' => $paperType['finish'] ?? null,
                'thickness' => $paperType['thickness'] ?? null,
                'api_data' => $paperType // Conserver les données originales
            ];
        }
        
        return $formattedTypes;
    }
    
    /**
     * Données de fallback pour les types de papier
     */
    private function getFallbackPaperTypes(): array
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
        $url = $this->getBaseUrl() . $endpoint;
        
        $options = [
            'headers' => [
                'X-API-Key' => $this->getApiKey(),
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
            throw new \Exception("Prodigi API Error (HTTP {$statusCode}): " . $content);
        }

        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from Prodigi API: ' . json_last_error_msg());
        }

        return $data;
    }
    
    /**
     * Synchroniser toutes les données avec l'API Prodigi
     * Utilisé pour forcer une mise à jour depuis l'admin
     */
    public function syncAllDataFromApi(): array
    {
        $result = [
            'success' => true,
            'formats_count' => 0,
            'paper_types_count' => 0,
            'errors' => []
        ];
        
        try {
            // Forcer un nouvel appel API en vidant le cache si nécessaire
            $formats = $this->getAvailableFormats();
            $result['formats_count'] = count($formats);
            
            $paperTypes = $this->getAvailablePaperTypes();
            $result['paper_types_count'] = count($paperTypes);
            
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Obtenir le statut de la connexion API Prodigi
     */
    public function getApiStatus(): array
    {
        // Si le wrapper est disponible, l'utiliser
        if ($this->useApiWrapper && $this->wrapperService) {
            $wrapperStatus = $this->wrapperService->getApiStatus();
            return [
                'connected' => $wrapperStatus['api_enabled'],
                'api_version' => 'v4.0',
                'environment' => $wrapperStatus['environment'],
                'wrapper_enabled' => true,
                'api_key_configured' => $wrapperStatus['api_key_configured'],
                'last_check' => $wrapperStatus['last_check']
            ];
        }
        
        // Test avec un produit spécifique qui existe (pas l'endpoint /products qui n'existe pas)
        try {
            // Tester avec un SKU connu qui devrait exister
            $testSku = 'GLOBAL-PAP-10X10'; // Un SKU standard qui devrait toujours exister
            $response = $this->makeRequest('GET', "/v4.0/products/{$testSku}");
            $data = $this->handleResponse($response);
            
            if (isset($data['product']) && !empty($data['product'])) {
                return [
                    'connected' => true,
                    'api_version' => 'v4.0',
                    'environment' => $this->useSandbox ? 'sandbox' : 'production',
                    'test_product' => $data['product']['sku'] ?? $testSku,
                    'wrapper_enabled' => false,
                    'last_check' => new \DateTime()
                ];
            }
            
            throw new \Exception('Réponse API invalide');
            
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Provide more helpful error messages
            if (strpos($errorMessage, 'HTTP 401') !== false) {
                $errorMessage = "Clé API invalide ou expirée. Vérifiez votre configuration.";
            } elseif (strpos($errorMessage, 'HTTP 404') !== false) {
                $errorMessage = "Produit de test non trouvé. L'API fonctionne mais ce SKU n'existe pas.";
            } elseif (strpos($errorMessage, 'HTTP 403') !== false) {
                $errorMessage = "Accès refusé. Votre compte Prodigi n'a pas les bonnes permissions.";
            }
            
            return [
                'connected' => false,
                'error' => $errorMessage,
                'environment' => $this->useSandbox ? 'sandbox' : 'production',
                'wrapper_enabled' => false,
                'last_check' => new \DateTime()
            ];
        }
    }
}