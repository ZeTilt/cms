<?php

namespace App\Controller\Admin;

use App\Entity\ApiConfiguration;
use App\Service\ProdigiApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/api-config')]
#[IsGranted('ROLE_ADMIN')]
class ApiConfigurationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProdigiApiService $prodigiApiService
    ) {}

    #[Route('', name: 'admin_api_config_index')]
    public function index(): Response
    {
        $apiConfigs = $this->entityManager->getRepository(ApiConfiguration::class)->findAll();
        
        // Créer la config Prodigi si elle n'existe pas
        $prodigiConfig = null;
        foreach ($apiConfigs as $config) {
            if ($config->getApiName() === 'prodigi') {
                $prodigiConfig = $config;
                break;
            }
        }
        
        if (!$prodigiConfig) {
            $prodigiConfig = new ApiConfiguration();
            $prodigiConfig->setApiName('prodigi');
            $prodigiConfig->setBaseUrl('https://api.sandbox.prodigi.com');
            $this->entityManager->persist($prodigiConfig);
            $this->entityManager->flush();
        }

        return $this->render('admin/api_config/index.html.twig', [
            'api_configs' => $apiConfigs,
            'prodigi_config' => $prodigiConfig
        ]);
    }

    #[Route('/save-prodigi', name: 'admin_api_config_save_prodigi', methods: ['POST'])]
    public function saveProdigiConfig(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->request->get('api_key');
            $baseUrl = $request->request->get('base_url');
            $isActive = $request->request->getBoolean('is_active');
            $isSandbox = $request->request->getBoolean('is_sandbox', true);

            // Validation
            if (empty($apiKey)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'La clé API est obligatoire'
                ]);
            }

            // Récupérer ou créer la config Prodigi
            $prodigiConfig = $this->entityManager->getRepository(ApiConfiguration::class)
                ->findOneBy(['apiName' => 'prodigi']);
            
            if (!$prodigiConfig) {
                $prodigiConfig = new ApiConfiguration();
                $prodigiConfig->setApiName('prodigi');
            }

            $prodigiConfig->setApiKey($apiKey);
            $prodigiConfig->setBaseUrl($baseUrl ?: ($isSandbox ? 'https://api.sandbox.prodigi.com' : 'https://api.prodigi.com'));
            $prodigiConfig->setIsActive($isActive);
            
            // Stocker l'info sandbox dans la config additionnelle
            $prodigiConfig->setAdditionalConfig(['sandbox' => $isSandbox]);

            $this->entityManager->persist($prodigiConfig);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Configuration Prodigi sauvegardée avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/test-prodigi', name: 'admin_api_config_test_prodigi', methods: ['POST'])]
    public function testProdigiConnection(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->request->get('api_key');
            $isSandbox = $request->request->getBoolean('is_sandbox', true);

            if (empty($apiKey)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Clé API requise pour le test'
                ]);
            }

            // Test temporaire avec les nouvelles clés
            $httpClient = $this->prodigiApiService->getHttpClient();
            $testService = new ProdigiApiService(
                $httpClient,
                $apiKey,
                $isSandbox
            );

            $status = $testService->getApiStatus();

            if ($status['connected']) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Connexion Prodigi réussie !',
                    'data' => [
                        'api_version' => $status['api_version'] ?? 'unknown',
                        'environment' => $status['environment'] ?? 'sandbox'
                    ]
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Impossible de se connecter à Prodigi: ' . ($status['error'] ?? 'Erreur inconnue')
                ]);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors du test: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sync-prodigi', name: 'admin_api_config_sync_prodigi', methods: ['POST'])]
    public function syncWithProdigi(): JsonResponse
    {
        try {
            // Vérifier que Prodigi est configuré
            $prodigiConfig = $this->entityManager->getRepository(ApiConfiguration::class)
                ->findOneBy(['apiName' => 'prodigi']);

            if (!$prodigiConfig || !$prodigiConfig->isConfigured()) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Configuration Prodigi manquante ou incomplète'
                ]);
            }

            // Effectuer la synchronisation (TOUJOURS en production pour les produits)
            $syncResult = $this->prodigiApiService->syncAllDataFromApi();

            if ($syncResult['success']) {
                $message = sprintf(
                    'Synchronisation réussie depuis l\'API PRODUCTION: %d formats et %d types de papier récupérés',
                    $syncResult['formats_count'],
                    $syncResult['paper_types_count']
                );

                return new JsonResponse([
                    'success' => true,
                    'message' => $message,
                    'data' => $syncResult,
                    'note' => 'Les produits sont toujours récupérés depuis l\'API de production'
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Erreurs de synchronisation: ' . implode(', ', $syncResult['errors'])
                ]);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la synchronisation: ' . $e->getMessage()
            ], 500);
        }
    }
}