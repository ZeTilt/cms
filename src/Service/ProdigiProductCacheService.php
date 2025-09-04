<?php

namespace App\Service;

use App\Entity\ProdigiProduct;
use App\Repository\ProdigiProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de cache intelligent pour les produits Prodigi
 * Optimise les performances en stockant les produits en BDD
 * et en rafraîchissant seulement les plus anciens
 */
class ProdigiProductCacheService
{
    private const MAX_REFRESH_PER_REQUEST = 10; // Max 10 appels API par requête
    private const MAX_AGE_HOURS = 24; // 24h avant expiration
    private const BATCH_SIZE = 5; // Traiter par batch de 5

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProdigiProductRepository $repository,
        private ProdigiProductService $productService,
        private ProdigiApiService $apiService,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * 🚀 NOUVELLE VERSION: Récupérer tous les produits avec cache RAPIDE
     * Les appels API sont différés pour ne PAS bloquer l'affichage
     */
    public function getAllProducts(): array
    {
        $startTime = microtime(true);
        
        // 1. TOUJOURS récupérer depuis le cache BDD d'abord (ULTRA RAPIDE)
        $cachedProducts = $this->repository->findAllAvailable();
        
        // 2. Si on a déjà des produits en cache, les retourner IMMÉDIATEMENT
        if (!empty($cachedProducts)) {
            
            // 🔄 ASYNC: Planifier un refresh en arrière-plan (sans bloquer)
            $this->scheduleBackgroundRefresh();
            
            $result = $this->formatCachedProducts($cachedProducts);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger?->info('Cache Prodigi RAPIDE utilisé', [
                'cached_products' => count($cachedProducts),
                'duration_ms' => $duration,
                'background_refresh' => 'Planifié'
            ]);
            
            return $result;
        }
        
        // 3. SEULEMENT si cache complètement vide: refresh minimal SYNCHRONE
        $this->logger?->warning('Cache vide, initialisation urgente avec SKUs de base');
        
        // Récupérer seulement les SKUs les plus importants pour l'initialisation
        $essentialSkus = $this->getEssentialSkus();
        $refreshCount = $this->refreshSkus($essentialSkus, 'Init cache vide');
        
        // Recharger et retourner
        $this->entityManager->clear();
        $cachedProducts = $this->repository->findAllAvailable();
        $result = $this->formatCachedProducts($cachedProducts);
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        $this->logger?->info('Cache initialisé d\'urgence', [
            'essential_products' => count($cachedProducts),
            'duration_ms' => $duration,
            'refreshed_count' => $refreshCount
        ]);
        
        return $result;
    }

    /**
     * Planifier un refresh en arrière-plan (SANS bloquer l'UI)
     * Pour l'instant, on ne fait que logger - le vrai refresh se fait par commande console
     */
    private function scheduleBackgroundRefresh(): void
    {
        $oldProductsCount = $this->repository->countOldProducts(self::MAX_AGE_HOURS);
        
        if ($oldProductsCount > 0) {
            $this->logger?->info('Refresh en arrière-plan nécessaire', [
                'old_products_count' => $oldProductsCount,
                'action' => 'Use console command: php bin/console prodigi:refresh-products'
            ]);
        }
    }

    /**
     * SKUs essentiels pour initialiser un cache vide rapidement
     */
    private function getEssentialSkus(): array
    {
        return [
            // Quelques produits de base pour chaque catégorie
            'GLOBAL-PAP-10X15',      // Tirage classique
            'GLOBAL-FAP-A4',         // Grand format
            'GLOBAL-CAN-20X30',      // Canvas
            'GLOBAL-MUG-11OZ',       // Cadeau
            'GLOBAL-WOOD-11X14-NAT-NOBDR', // Déco
            'GLOBAL-BOOK-HARD',      // Livre
            'GLOBAL-CARD-CLASSIC',   // Carte
            'GLOBAL-CAL-WALL',       // Calendrier
            'GLOBAL-JIG-PUZ-A4',     // Puzzle
            'GLOBAL-TEE-BC-3200',    // Vêtement
        ];
    }

    /**
     * Rafraîchir une liste de SKUs depuis l'API
     */
    private function refreshSkus(array $skus, string $reason): int
    {
        $refreshed = 0;
        
        foreach ($skus as $sku) {
            try {
                $productData = $this->apiService->getProduct($sku);
                if ($productData && isset($productData['product'])) {
                    $this->saveOrUpdateProduct($sku, $productData);
                    $refreshed++;
                    
                    $this->logger?->debug("Produit {$sku} rafraîchi", [
                        'reason' => $reason,
                        'success' => true
                    ]);
                } else {
                    // Marquer comme indisponible si l'API ne retourne rien
                    $this->markProductUnavailable($sku);
                    
                    $this->logger?->warning("Produit {$sku} indisponible", [
                        'reason' => $reason,
                        'api_response' => 'empty'
                    ]);
                }
            } catch (\Exception $e) {
                $this->logger?->error("Erreur refresh SKU {$sku}", [
                    'reason' => $reason,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        if ($refreshed > 0) {
            $this->entityManager->flush();
        }
        
        return $refreshed;
    }

    /**
     * Sauvegarder ou mettre à jour un produit en BDD
     */
    private function saveOrUpdateProduct(string $sku, array $apiData): void
    {
        $product = $this->repository->findOneBySku($sku);
        if (!$product) {
            $product = new ProdigiProduct();
            $product->setSku($sku);
            $this->entityManager->persist($product);
        }

        // Formater les données comme le ferait ProdigiProductService
        $formattedData = $this->formatProductFromApi($sku, $apiData);
        
        $product
            ->setName($formattedData['name'])
            ->setDescription($formattedData['description'])
            ->setCategory($formattedData['category'])
            ->setBasePrice($formattedData['base_price'])
            ->setPaperType($formattedData['paper_type'])
            ->setDimensions($formattedData['dimensions'])
            ->setAttributes($formattedData['attributes'])
            ->setApiData($apiData)
            ->setIsAvailable(true)
            ->touch();
    }

    /**
     * Marquer un produit comme indisponible
     */
    private function markProductUnavailable(string $sku): void
    {
        $product = $this->repository->findOneBySku($sku);
        if ($product) {
            $product->setIsAvailable(false)->touch();
        }
    }

    /**
     * Formater les données API comme le fait ProdigiProductService
     */
    private function formatProductFromApi(string $sku, array $apiData): array
    {
        $productData = $apiData['product'] ?? [];
        
        return [
            'sku' => $sku,
            'name' => $this->generateProductName($sku, $productData),
            'description' => $productData['description'] ?? '',
            'category' => $this->getCategoryFromSku($sku),
            'base_price' => $this->estimateBasePrice($sku, $productData['productDimensions'] ?? []),
            'dimensions' => $productData['productDimensions'] ?? [],
            'attributes' => $productData['attributes'] ?? [],
            'paper_type' => $this->getPaperTypeFromSku($sku),
            'available' => true
        ];
    }

    /**
     * Formater les produits cachés pour l'interface
     */
    private function formatCachedProducts(array $cachedProducts): array
    {
        $products = [];
        
        foreach ($cachedProducts as $product) {
            $products[$product->getSku()] = [
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'category' => $product->getCategory(),
                'base_price' => $product->getBasePrice(),
                'dimensions' => $product->getDimensions(),
                'attributes' => $product->getAttributes(),
                'paper_type' => $product->getPaperType(),
                'available' => $product->isAvailable()
            ];
        }
        
        return [
            'products' => $products,
            'total_attempted' => count($products),
            'successful' => count($products),
            'errors' => [] // Plus d'erreurs avec le cache !
        ];
    }

    /**
     * Force la synchronisation complète (pour l'admin)
     */
    public function forceSyncAllProducts(): array
    {
        $allSkus = $this->productService->getAvailableSkus();
        $refreshed = $this->refreshSkus($allSkus, 'Sync forcée');
        
        return [
            'success' => $refreshed > 0,
            'formats_count' => $refreshed,
            'paper_types_count' => $this->countUniquePaperTypes(),
            'errors' => []
        ];
    }

    /**
     * Compter les types de papier uniques
     */
    private function countUniquePaperTypes(): int
    {
        return count(array_unique(
            array_map(
                fn($p) => $p->getPaperType(), 
                $this->repository->findAllAvailable()
            )
        ));
    }

    // Réutiliser les méthodes du ProdigiProductService
    private function generateProductName(string $sku, array $product): string
    {
        $dimensions = $product['productDimensions'] ?? [];
        
        if (isset($dimensions['width'], $dimensions['height'], $dimensions['units'])) {
            $width = $dimensions['width'];
            $height = $dimensions['height'];
            $units = $dimensions['units'] === 'in' ? '"' : $dimensions['units'];
            
            $paperType = $this->getPaperTypeFromSku($sku);
            
            return "{$width}x{$height}{$units} {$paperType}";
        }
        
        return $sku;
    }

    private function getCategoryFromSku(string $sku): string
    {
        // Réutiliser la logique de ProdigiProductService
        if (strpos($sku, 'PUZ-') !== false || strpos($sku, '-PUZ-') !== false || strpos($sku, 'JIG-') !== false) {
            return 'puzzles';
        }
        
        if (strpos($sku, 'MUG-') !== false || strpos($sku, '-MUG-') !== false || 
            strpos($sku, 'TUM-') !== false || strpos($sku, '-TUM-') !== false) {
            return 'cadeaux';
        }
        
        if (strpos($sku, 'TEE-') !== false || strpos($sku, '-TEE-') !== false ||
            strpos($sku, 'HOD-') !== false || strpos($sku, '-HOD-') !== false ||
            strpos($sku, 'SWE-') !== false || strpos($sku, '-SWE-') !== false ||
            strpos($sku, 'BODY-') !== false || strpos($sku, '-BODY-') !== false) {
            return 'cadeaux';
        }
        
        if (strpos($sku, 'BAG-') !== false || strpos($sku, '-BAG-') !== false ||
            strpos($sku, 'MASK-') !== false || strpos($sku, '-MASK-') !== false ||
            strpos($sku, 'SOCK-') !== false || strpos($sku, '-SOCK-') !== false ||
            strpos($sku, 'CUSHION') !== false || strpos($sku, 'PILLOW') !== false ||
            strpos($sku, 'APRON') !== false || strpos($sku, 'TOWEL') !== false ||
            strpos($sku, 'BLANKET') !== false) {
            return 'cadeaux';
        }
        
        if (strpos($sku, 'CASE-') !== false || strpos($sku, '-CASE-') !== false ||
            strpos($sku, 'MOUSE') !== false || strpos($sku, 'LAPTOP') !== false ||
            strpos($sku, 'WATCH') !== false || strpos($sku, 'GAMING') !== false) {
            return 'cadeaux';
        }
        
        if (strpos($sku, 'TATT-') !== false || strpos($sku, '-TATT-') !== false) {
            return 'cadeaux';
        }
        
        if (strpos($sku, 'BOOK-') !== false || strpos($sku, '-BOOK-') !== false) {
            return 'livres';
        }
        
        if (strpos($sku, 'CARD-') !== false || strpos($sku, '-CARD-') !== false ||
            strpos($sku, 'POST-') !== false || strpos($sku, '-POST-') !== false ||
            strpos($sku, 'NOTE-') !== false || strpos($sku, '-NOTE-') !== false ||
            strpos($sku, 'DIARY') !== false || strpos($sku, 'PLAN-') !== false ||
            strpos($sku, 'INVITATION') !== false || strpos($sku, 'WRAP') !== false) {
            return 'cartes';
        }
        
        if (strpos($sku, 'CAL-') !== false || strpos($sku, '-CAL-') !== false) {
            return 'calendriers';
        }
        
        if (strpos($sku, 'CAN-') !== false || strpos($sku, '-CAN-') !== false ||
            strpos($sku, 'ALU-') !== false || strpos($sku, '-ALU-') !== false ||
            strpos($sku, 'DIB-') !== false || strpos($sku, '-DIB-') !== false ||
            strpos($sku, 'WOOD-') !== false || strpos($sku, '-WOOD-') !== false ||
            strpos($sku, 'PHOTIL-') !== false) {
            return 'decoration';
        }
        
        if (strpos($sku, 'FAP-') !== false || strpos($sku, '-FAP-') !== false) {
            return 'grands_formats';
        }
        
        if (strpos($sku, 'PAP-') !== false || strpos($sku, '-PAP-') !== false) {
            return 'tirages';
        }
        
        return 'tirages'; // Par défaut
    }

    private function getPaperTypeFromSku(string $sku): string
    {
        if (strpos($sku, 'CAN-') !== false || strpos($sku, '-CAN-') !== false) return 'Canvas';
        if (strpos($sku, 'FAP-') !== false || strpos($sku, '-FAP-') !== false) return 'Fine Art Paper';
        if (strpos($sku, 'PAP-') !== false || strpos($sku, '-PAP-') !== false) return 'Photo Paper';
        if (strpos($sku, 'ALU-') !== false || strpos($sku, '-ALU-') !== false) return 'Metal';
        if (strpos($sku, 'DIB-') !== false || strpos($sku, '-DIB-') !== false) return 'Metal';
        if (strpos($sku, 'WOOD-') !== false || strpos($sku, '-WOOD-') !== false) return 'Wood';
        if (strpos($sku, 'PUZ-') !== false || strpos($sku, 'JIG-') !== false) return 'Cardboard';
        if (strpos($sku, 'MUG-') !== false) return 'Ceramic';
        if (strpos($sku, 'TEE-') !== false || strpos($sku, 'HOD-') !== false || strpos($sku, 'CUSHION') !== false || strpos($sku, 'BAG-') !== false) return 'Fabric';
        if (strpos($sku, 'CASE-') !== false) return 'Plastic';
        if (strpos($sku, 'TATT-') !== false) return 'Vinyl';
        if (strpos($sku, 'BOOK-') !== false || strpos($sku, 'CARD-') !== false || strpos($sku, 'NOTE-') !== false || strpos($sku, 'CAL-') !== false) return 'Paper';
        
        return 'Standard';
    }

    private function estimateBasePrice(string $sku, array $dimensions): float
    {
        // Prix fixes pour certains types
        if (strpos($sku, 'PUZ-') !== false || strpos($sku, 'JIG-') !== false) return 24.99;
        if (strpos($sku, 'MUG-') !== false) return 12.99;
        if (strpos($sku, 'TEE-') !== false) return 19.99;
        if (strpos($sku, 'HOD-') !== false) return 39.99;
        if (strpos($sku, 'BAG-') !== false) return 16.99;
        if (strpos($sku, 'CUSHION') !== false || strpos($sku, 'PILLOW') !== false) return 24.99;
        if (strpos($sku, 'CASE-') !== false) return 24.99;
        if (strpos($sku, 'TATT-') !== false) return 3.99;
        if (strpos($sku, 'BOOK-') !== false) return 19.99;
        if (strpos($sku, 'CARD-') !== false) return 2.99;
        if (strpos($sku, 'CAL-') !== false) return 12.99;
        
        // Calcul basé sur les dimensions
        $width = $dimensions['width'] ?? 10;
        $height = $dimensions['height'] ?? 10;
        $area = $width * $height;
        
        $baseRate = 0.02;
        if (strpos($sku, 'ALU-') !== false || strpos($sku, 'DIB-') !== false) $baseRate *= 4;
        elseif (strpos($sku, 'WOOD-') !== false) $baseRate *= 3.5;
        elseif (strpos($sku, 'CAN-') !== false) $baseRate *= 3;
        elseif (strpos($sku, 'FAP-') !== false) $baseRate *= 2;
        
        $minPrice = 2.99;
        if (strpos($sku, 'WOOD-') !== false) $minPrice = 29.99;
        if (strpos($sku, 'ALU-') !== false || strpos($sku, 'DIB-') !== false) $minPrice = 19.99;
        if (strpos($sku, 'CAN-') !== false) $minPrice = 14.99;
        if (strpos($sku, 'FAP-') !== false) $minPrice = 8.99;
        
        return round(max($area * $baseRate, $minPrice), 2);
    }

    /**
     * Rafraîchir seulement les N produits les plus anciens
     */
    public function refreshOldestProducts(int $limit = 10): array
    {
        $oldProducts = $this->repository->findOldestProducts($limit, self::MAX_AGE_HOURS);
        $oldSkus = array_map(fn($p) => $p->getSku(), $oldProducts);
        
        $refreshed = $this->refreshSkus($oldSkus, "Refresh des {$limit} plus anciens");
        
        return [
            'refreshed_count' => $refreshed,
            'old_products_found' => count($oldProducts),
            'success' => $refreshed > 0
        ];
    }

    /**
     * Obtenir les statistiques du cache
     */
    public function getCacheStats(): array
    {
        return $this->repository->getCacheStats();
    }

    /**
     * Obtenir les types de papier depuis le cache (SANS appel API)
     */
    public function getAvailablePaperTypesFromCache(): array
    {
        $cachedProducts = $this->repository->findAllAvailable();
        $paperTypes = [];
        
        foreach ($cachedProducts as $product) {
            $paperType = $product->getPaperType();
            if (!empty($paperType) && !isset($paperTypes[$paperType])) {
                $typeKey = strtolower(str_replace([' ', '-'], '_', $paperType));
                $paperTypes[$typeKey] = [
                    'name' => $paperType,
                    'description' => $this->getPaperTypeDescription($paperType),
                    'price_multiplier' => $this->getPaperTypeMultiplier($paperType)
                ];
            }
        }
        
        return $paperTypes;
    }

    /**
     * Obtenir le statut API depuis le cache (pas d'appel réseau)
     */
    public function getCachedApiStatus(): array
    {
        $stats = $this->repository->getCacheStats();
        
        // Récupérer la date de dernière mise à jour du cache
        $lastProduct = $this->repository->findOneBy([], ['lastUpdatedAt' => 'DESC']);
        $lastCheck = $lastProduct ? $lastProduct->getLastUpdatedAt() : new \DateTimeImmutable();
        
        return [
            'connected' => $stats['available_products'] > 0,
            'last_check' => $lastCheck,  // DateTime object comme attendu par le template
            'last_sync' => $lastCheck->format('Y-m-d H:i:s'),
            'total_products' => $stats['available_products'],
            'message' => 'Données depuis cache local (BDD)',
            'environment' => 'Cache Database',
            'fallback_mode' => false
        ];
    }

    /**
     * Obtenir les catégories depuis le cache (pas d'appel API)
     */
    public function getCachedProductCategories(): array
    {
        return [
            'tirages' => [
                'name' => 'Tirages Photo',
                'description' => 'Tirages sur papier photo classique'
            ],
            'grands_formats' => [
                'name' => 'Grands Formats', 
                'description' => 'Tirages A3, A2, A1 sur papier fine art'
            ],
            'decoration' => [
                'name' => 'Décoration',
                'description' => 'Canvas, aluminium, bois pour décoration murale'
            ],
            'cadeaux' => [
                'name' => 'Cadeaux Personnalisés',
                'description' => 'Mugs, puzzles, textile, accessoires'
            ],
            'livres' => [
                'name' => 'Livres Photo',
                'description' => 'Albums photo rigides et souples'
            ],
            'cartes' => [
                'name' => 'Cartes & Papeterie',
                'description' => 'Cartes de vœux, cartes postales, carnets'
            ],
            'calendriers' => [
                'name' => 'Calendriers',
                'description' => 'Calendriers muraux et de bureau'
            ],
            'autres' => [
                'name' => 'Autres Produits',
                'description' => 'Produits divers et spécialisés'
            ]
        ];
    }

    private function getPaperTypeDescription(string $paperType): string
    {
        $descriptions = [
            'Photo Paper' => 'Papier photo brillant classique',
            'Fine Art Paper' => 'Papier fine art mat premium',
            'Canvas' => 'Toile canvas texturée',
            'Metal' => 'Aluminium ou dibond',
            'Wood' => 'Support bois naturel',
            'Cardboard' => 'Carton épais pour puzzles',
            'Ceramic' => 'Céramique pour mugs',
            'Fabric' => 'Textile pour vêtements',
            'Plastic' => 'Plastique pour coques',
            'Vinyl' => 'Vinyl pour autocollants',
            'Paper' => 'Papier standard',
        ];
        
        return $descriptions[$paperType] ?? 'Matériau spécialisé';
    }

    private function getPaperTypeMultiplier(string $paperType): float
    {
        $multipliers = [
            'Photo Paper' => 1.0,
            'Fine Art Paper' => 2.0,
            'Canvas' => 3.0,
            'Metal' => 4.0,
            'Wood' => 3.5,
            'Cardboard' => 1.5,
            'Ceramic' => 2.5,
            'Fabric' => 2.0,
            'Plastic' => 2.2,
            'Vinyl' => 1.8,
            'Paper' => 1.2,
        ];
        
        return $multipliers[$paperType] ?? 1.0;
    }

    /**
     * Ajouter des SKUs personnalisés avec vérification d'existence
     */
    public function addCustomSkus(array $skus): array
    {
        $results = [
            'added' => [],
            'already_exists' => [],
            'failed' => [],
            'total_attempted' => count($skus)
        ];

        foreach ($skus as $sku) {
            $sku = trim(strtoupper($sku)); // Normaliser le SKU
            
            if (empty($sku)) {
                continue;
            }
            
            // Vérifier si le SKU existe déjà
            $existingProduct = $this->repository->findOneBySku($sku);
            if ($existingProduct) {
                $results['already_exists'][] = $sku;
                continue;
            }
            
            // Essayer de récupérer le produit depuis l'API
            try {
                $productData = $this->apiService->getProduct($sku);
                if ($productData && isset($productData['product'])) {
                    $this->saveOrUpdateProduct($sku, $productData);
                    $results['added'][] = $sku;
                } else {
                    $results['failed'][] = ['sku' => $sku, 'reason' => 'Produit introuvable dans l\'API'];
                }
            } catch (\Exception $e) {
                $results['failed'][] = ['sku' => $sku, 'reason' => $e->getMessage()];
            }
        }
        
        // Sauvegarder les changements
        if (!empty($results['added'])) {
            $this->entityManager->flush();
        }
        
        return $results;
    }

    /**
     * Supprimer un SKU du cache
     */
    public function removeCustomSku(string $sku): bool
    {
        $product = $this->repository->findOneBySku($sku);
        if ($product) {
            $this->entityManager->remove($product);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    /**
     * Lister tous les SKUs en cache avec leurs informations
     */
    public function listAllSkus(): array
    {
        $products = $this->repository->findAllAvailable();
        $skuList = [];
        
        foreach ($products as $product) {
            $skuList[] = [
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'category' => $product->getCategory(),
                'paper_type' => $product->getPaperType(),
                'base_price' => $product->getBasePrice(),
                'last_updated' => $product->getLastUpdatedAt()->format('Y-m-d H:i:s'),
                'is_recent' => $product->getLastUpdatedAt() > new \DateTimeImmutable('-24 hours')
            ];
        }
        
        // Trier par SKU
        usort($skuList, fn($a, $b) => strcmp($a['sku'], $b['sku']));
        
        return $skuList;
    }
}