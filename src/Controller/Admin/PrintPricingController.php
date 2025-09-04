<?php

namespace App\Controller\Admin;

use App\Service\ProdigiApiService;
use App\Service\ProdigiProductCacheService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/print')]
#[IsGranted('ROLE_ADMIN')]
class PrintPricingController extends AbstractController
{
    public function __construct(
        private ProdigiApiService $prodigiApiService,
        private ProdigiProductCacheService $cacheService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/pricing', name: 'admin_print_pricing', methods: ['GET'])]
    public function index(): Response
    {
        // 🚀 NOUVEAU: Utiliser le cache intelligent (max 10 appels API)
        $cachedResult = $this->cacheService->getAllProducts();
        $formats = $cachedResult['products'];
        
        // Grouper par catégorie
        $formatsByCategory = [];
        foreach ($formats as $sku => $format) {
            $category = $format['category'];
            $formatsByCategory[$category][$sku] = $format;
        }
        
        // 🚀 NOUVEAU: Utiliser le cache au lieu des appels API
        $categories = $this->cacheService->getCachedProductCategories();
        $paperTypes = $this->cacheService->getAvailablePaperTypesFromCache();
        
        // Get custom margins from database
        $customMargins = $this->getCustomMargins($formats);
        
        // Get API status for display (depuis cache, pas d'appel réseau)
        $apiStatus = $this->cacheService->getCachedApiStatus();

        return $this->render('admin/print_pricing/index.html.twig', [
            'formats' => $formats,
            'formats_by_category' => $formatsByCategory,
            'categories' => $categories,
            'paper_types' => $paperTypes,
            'custom_margins' => $customMargins,
            'api_status' => $apiStatus
        ]);
    }

    #[Route('/pricing/update-margins', name: 'admin_print_pricing_update_margins', methods: ['POST'])]
    public function updateMargins(Request $request): JsonResponse
    {
        try {
            $margins = $request->request->all('margins');
            
            // Validate margins
            foreach ($margins as $key => $margin) {
                if (!is_numeric($margin) || $margin < 0) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => "Marge invalide pour {$key}"
                    ]);
                }
            }

            // Store margins in database
            $this->storeCustomMargins($margins);

            return new JsonResponse([
                'success' => true,
                'message' => 'Marges mises à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/pricing/sync-prodigi', name: 'admin_print_pricing_sync_prodigi', methods: ['POST'])]
    public function syncWithProdigi(): JsonResponse
    {
        try {
            // 🚀 NOUVEAU: Synchronisation COMPLÈTE forcée (TOUS les SKUs)
            $syncResult = $this->cacheService->forceSyncAllProducts();
            
            if ($syncResult['success']) {
                $message = sprintf(
                    'Synchronisation COMPLÈTE Prodigi réussie: %d produits mis à jour, %d types de matériaux',
                    $syncResult['formats_count'],
                    $syncResult['paper_types_count']
                );
                
                return new JsonResponse([
                    'success' => true,
                    'message' => $message,
                    'data' => $syncResult,
                    'note' => 'Synchronisation forcée complète de tous les SKUs depuis l\'API de production'
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Aucun produit n\'a pu être synchronisé'
                ], 500);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la synchronisation: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/pricing/calculate-customer-price', name: 'admin_print_pricing_calculate', methods: ['POST'])]
    public function calculateCustomerPrice(Request $request): JsonResponse
    {
        try {
            $format = $request->request->get('format');
            $paperType = $request->request->get('paper_type');
            $quantity = (int) $request->request->get('quantity', 1);

            if (!$format || !$paperType) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Paramètres manquants'
                ]);
            }

            // Utiliser le cache pour les formats
            $cachedResult = $this->cacheService->getAllProducts();
            $formats = $cachedResult['products'];
            $paperTypes = $this->cacheService->getAvailablePaperTypesFromCache();

            if (!isset($formats[$format]) || !isset($paperTypes[$paperType])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Format ou type de papier invalide'
                ]);
            }

            // Calculate base price
            $basePrice = $formats[$format]['base_price'];
            $multiplier = $paperTypes[$paperType]['price_multiplier'];
            $prodigiPrice = $basePrice * $multiplier;

            // Get custom margin
            $customMargins = $this->getCustomMargins();
            $marginKey = $format . '_' . $paperType;
            $margin = $customMargins[$marginKey] ?? $customMargins['default'] ?? 50; // 50% default margin

            // Calculate customer price
            $customerPrice = $prodigiPrice * (1 + ($margin / 100));
            $totalPrice = $customerPrice * $quantity;

            return new JsonResponse([
                'success' => true,
                'prodigi_price' => number_format($prodigiPrice, 3),
                'customer_price' => number_format($customerPrice, 2),
                'margin_percent' => $margin,
                'margin_amount' => number_format($customerPrice - $prodigiPrice, 3),
                'quantity' => $quantity,
                'total_price' => number_format($totalPrice, 2)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getCustomMargins(array $formats = []): array
    {
        // For now, we'll use a simple approach with a JSON configuration file
        // In a more robust solution, this could be stored in a database table
        $configFile = $this->getParameter('kernel.project_dir') . '/config/print_margins.json';
        
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            $margins = json_decode($content, true) ?: [];
        } else {
            $margins = [];
        }

        // Si aucun format fourni, utiliser le cache
        if (empty($formats)) {
            $cachedResult = $this->cacheService->getAllProducts();
            $formats = $cachedResult['products'];
        }
        
        $paperTypes = $this->cacheService->getAvailablePaperTypesFromCache();
        
        // Set global default if not present
        if (!isset($margins['default'])) {
            $margins['default'] = 50; // 50% margin by default
        }
        
        // Initialize individual product margins if not set
        foreach ($formats as $formatKey => $format) {
            foreach ($paperTypes as $paperKey => $paper) {
                $marginKey = $formatKey . '_' . $paperKey;
                if (!isset($margins[$marginKey])) {
                    // Use category-specific defaults or global default
                    $categoryDefault = $this->getCategoryDefaultMargin($format['category']);
                    $margins[$marginKey] = $categoryDefault ?? $margins['default'];
                }
            }
        }
        
        return $margins;
    }
    
    private function getCategoryDefaultMargin(string $category): ?float
    {
        // Different default margins per category
        $categoryDefaults = [
            'tirages' => 50,        // 50% pour tirages classiques
            'grands_formats' => 40,  // 40% pour grands formats
            'puzzles' => 60,        // 60% pour puzzles
            'cadeaux' => 70,        // 70% pour cadeaux personnalisés
            'decoration' => 65,     // 65% pour décoration
            'calendriers' => 55,    // 55% pour calendriers
            'livres' => 50,         // 50% pour livres photo
            'cartes' => 75,         // 75% pour cartes
            'autres' => 60          // 60% pour produits divers
        ];
        
        return $categoryDefaults[$category] ?? null;
    }

    private function storeCustomMargins(array $margins): void
    {
        $configFile = $this->getParameter('kernel.project_dir') . '/config/print_margins.json';
        
        // Ensure config directory exists
        $configDir = dirname($configFile);
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        file_put_contents($configFile, json_encode($margins, JSON_PRETTY_PRINT));
    }

    #[Route('/pricing/manage-skus', name: 'admin_print_pricing_manage_skus', methods: ['GET'])]
    public function manageSkus(): Response
    {
        // Lister tous les SKUs actuellement en cache
        $skuList = $this->cacheService->listAllSkus();
        $cacheStats = $this->cacheService->getCacheStats();
        
        return $this->render('admin/print_pricing/manage_skus.html.twig', [
            'sku_list' => $skuList,
            'cache_stats' => $cacheStats
        ]);
    }

    #[Route('/pricing/add-skus', name: 'admin_print_pricing_add_skus', methods: ['POST'])]
    public function addSkus(Request $request): JsonResponse
    {
        try {
            $skusInput = $request->request->get('skus', '');
            
            if (empty($skusInput)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Aucun SKU fourni'
                ]);
            }
            
            // Parser les SKUs (ligne par ligne ou séparés par virgule/espace)
            $skus = array_filter(
                array_map('trim', 
                    preg_split('/[\r\n,\s]+/', $skusInput)
                )
            );
            
            if (empty($skus)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Aucun SKU valide trouvé'
                ]);
            }
            
            // Ajouter les SKUs via le service cache
            $results = $this->cacheService->addCustomSkus($skus);
            
            $message = sprintf(
                'Traitement terminé: %d ajoutés, %d déjà existants, %d échoués sur %d tentatives',
                count($results['added']),
                count($results['already_exists']),
                count($results['failed']),
                $results['total_attempted']
            );
            
            return new JsonResponse([
                'success' => true,
                'message' => $message,
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            // Logger l'erreur complète pour le debugging
            error_log('Erreur ajout SKUs: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de l\'ajout des SKUs: ' . $e->getMessage(),
                'debug' => [
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile()),
                    'trace' => explode("\n", $e->getTraceAsString())[0] ?? 'Pas de trace'
                ]
            ], 500);
        }
    }

    #[Route('/pricing/remove-sku/{sku}', name: 'admin_print_pricing_remove_sku', methods: ['DELETE'])]
    public function removeSku(string $sku): JsonResponse
    {
        try {
            $success = $this->cacheService->removeCustomSku($sku);
            
            if ($success) {
                return new JsonResponse([
                    'success' => true,
                    'message' => "SKU {$sku} supprimé du cache"
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => "SKU {$sku} non trouvé"
                ], 404);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }
}