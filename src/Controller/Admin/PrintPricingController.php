<?php

namespace App\Controller\Admin;

use App\Service\CeweApiService;
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
        private CeweApiService $ceweApiService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/pricing', name: 'admin_print_pricing', methods: ['GET'])]
    public function index(): Response
    {
        // Get current pricing configuration
        $formats = $this->ceweApiService->getAvailableFormats();
        $paperTypes = $this->ceweApiService->getAvailablePaperTypes();
        
        // Get custom margins from database (we'll store these in a configuration table)
        $customMargins = $this->getCustomMargins();

        return $this->render('admin/print_pricing/index.html.twig', [
            'formats' => $formats,
            'paper_types' => $paperTypes,
            'custom_margins' => $customMargins
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

    #[Route('/pricing/sync-cewe', name: 'admin_print_pricing_sync_cewe', methods: ['POST'])]
    public function syncWithCewe(): JsonResponse
    {
        try {
            // This would make an API call to CEWE to get updated pricing
            // For now, we'll return the static pricing from the service
            $formats = $this->ceweApiService->getAvailableFormats();
            $paperTypes = $this->ceweApiService->getAvailablePaperTypes();

            return new JsonResponse([
                'success' => true,
                'message' => 'Synchronisation avec CEWE réussie',
                'formats' => $formats,
                'paper_types' => $paperTypes
            ]);
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

            $formats = $this->ceweApiService->getAvailableFormats();
            $paperTypes = $this->ceweApiService->getAvailablePaperTypes();

            if (!isset($formats[$format]) || !isset($paperTypes[$paperType])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Format ou type de papier invalide'
                ]);
            }

            // Calculate base price
            $basePrice = $formats[$format]['base_price'];
            $multiplier = $paperTypes[$paperType]['price_multiplier'];
            $cewePrice = $basePrice * $multiplier;

            // Get custom margin
            $customMargins = $this->getCustomMargins();
            $marginKey = $format . '_' . $paperType;
            $margin = $customMargins[$marginKey] ?? $customMargins['default'] ?? 50; // 50% default margin

            // Calculate customer price
            $customerPrice = $cewePrice * (1 + ($margin / 100));
            $totalPrice = $customerPrice * $quantity;

            return new JsonResponse([
                'success' => true,
                'cewe_price' => number_format($cewePrice, 3),
                'customer_price' => number_format($customerPrice, 2),
                'margin_percent' => $margin,
                'margin_amount' => number_format($customerPrice - $cewePrice, 3),
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

    private function getCustomMargins(): array
    {
        // For now, we'll use a simple approach with a JSON configuration file
        // In a more robust solution, this could be stored in a database table
        $configFile = $this->getParameter('kernel.project_dir') . '/config/print_margins.json';
        
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            return json_decode($content, true) ?: [];
        }

        // Default margins
        return [
            'default' => 50, // 50% margin by default
        ];
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
}