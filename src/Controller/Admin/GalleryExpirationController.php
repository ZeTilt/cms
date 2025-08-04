<?php

namespace App\Controller\Admin;

use App\Entity\Gallery;
use App\Repository\GalleryRepository;
use App\Service\GalleryExpirationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/galleries/expiration')]
#[IsGranted('ROLE_ADMIN')]
class GalleryExpirationController extends AbstractController
{
    public function __construct(
        private GalleryRepository $galleryRepository,
        private GalleryExpirationService $expirationService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_gallery_expiration_dashboard')]
    public function dashboard(): Response
    {
        $stats = $this->expirationService->getExpirationStats();
        
        $galleriesWithExpiration = $this->galleryRepository->findGalleriesWithExpiration();
        $expiredGalleries = $this->galleryRepository->findExpiredGalleries();
        $expiringSoon = $this->galleryRepository->findGalleriesExpiringWithin(7);

        return $this->render('admin/gallery_expiration/dashboard.html.twig', [
            'stats' => $stats,
            'galleries_with_expiration' => $galleriesWithExpiration,
            'expired_galleries' => $expiredGalleries,
            'expiring_soon' => $expiringSoon
        ]);
    }

    #[Route('/expired', name: 'admin_gallery_expiration_expired')]
    public function expiredGalleries(): Response
    {
        $expiredGalleries = $this->galleryRepository->findExpiredGalleries();
        
        return $this->render('admin/gallery_expiration/expired.html.twig', [
            'expired_galleries' => $expiredGalleries
        ]);
    }

    #[Route('/expiring-soon', name: 'admin_gallery_expiration_expiring_soon')]
    public function expiringSoonGalleries(Request $request): Response
    {
        $days = $request->query->getInt('days', 7);
        $expiringSoon = $this->galleryRepository->findGalleriesExpiringWithin($days);
        
        return $this->render('admin/gallery_expiration/expiring_soon.html.twig', [
            'expiring_galleries' => $expiringSoon,
            'days' => $days
        ]);
    }

    #[Route('/reactivate', name: 'admin_gallery_expiration_reactivate', methods: ['POST'])]
    public function reactivateGallery(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $galleryId = $data['galleryId'] ?? null;
            $duration = $data['duration'] ?? null;
            
            if (!$galleryId) {
                return $this->json(['success' => false, 'message' => 'Gallery ID is required'], 400);
            }
            
            $gallery = $this->galleryRepository->find($galleryId);
            if (!$gallery) {
                return $this->json(['success' => false, 'message' => 'Gallery not found'], 404);
            }
            
            if ($duration > 0) {
                $this->expirationService->reactivateGallery($gallery, $duration);
                $message = sprintf('Gallery "%s" reactivated with %d days duration', $gallery->getTitle(), $duration);
            } else {
                $this->expirationService->reactivateGallery($gallery);
                $message = sprintf('Gallery "%s" reactivated permanently (no expiration)', $gallery->getTitle());
            }
            
            return $this->json([
                'success' => true,
                'message' => $message,
                'gallery' => [
                    'id' => $gallery->getId(),
                    'title' => $gallery->getTitle(),
                    'visibility' => $gallery->getVisibility(),
                    'end_date' => $gallery->getEndDate()?->format('Y-m-d H:i:s'),
                    'days_until_expiration' => $gallery->getDaysUntilExpiration()
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to reactivate gallery: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/extend', name: 'admin_gallery_expiration_extend', methods: ['POST'])]
    public function extendGallery(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $galleryId = $data['galleryId'] ?? null;
            $additionalDays = $data['days'] ?? 30;
            
            if (!$galleryId) {
                return $this->json(['success' => false, 'message' => 'Gallery ID is required'], 400);
            }
            
            $gallery = $this->galleryRepository->find($galleryId);
            if (!$gallery) {
                return $this->json(['success' => false, 'message' => 'Gallery not found'], 404);
            }
            
            if ($gallery->getEndDate()) {
                $newEndDate = $gallery->getEndDate()->modify("+{$additionalDays} days");
            } else {
                $newEndDate = (new \DateTimeImmutable())->modify("+{$additionalDays} days");
            }
            
            $gallery->setEndDate($newEndDate);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => sprintf('Gallery "%s" extended by %d days', $gallery->getTitle(), $additionalDays),
                'gallery' => [
                    'id' => $gallery->getId(),
                    'title' => $gallery->getTitle(),
                    'end_date' => $gallery->getEndDate()->format('Y-m-d H:i:s'),
                    'days_until_expiration' => $gallery->getDaysUntilExpiration()
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to extend gallery: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/bulk-extend', name: 'admin_gallery_expiration_bulk_extend', methods: ['POST'])]
    public function bulkExtendGalleries(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $galleryIds = $data['galleryIds'] ?? [];
            $additionalDays = $data['days'] ?? 30;
            
            if (empty($galleryIds)) {
                return $this->json([
                    'success' => false,
                    'message' => 'No galleries selected'
                ], 400);
            }
            
            $galleries = $this->galleryRepository->findBy(['id' => $galleryIds]);
            $updated = 0;
            
            foreach ($galleries as $gallery) {
                if ($gallery->getEndDate()) {
                    $newEndDate = $gallery->getEndDate()->modify("+{$additionalDays} days");
                } else {
                    $newEndDate = (new \DateTimeImmutable())->modify("+{$additionalDays} days");
                }
                
                $gallery->setEndDate($newEndDate);
                $updated++;
            }
            
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => sprintf('%d galleries extended by %d days', $updated, $additionalDays),
                'updated_count' => $updated
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to bulk extend galleries: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/run-expiration-check', name: 'admin_gallery_expiration_run_check', methods: ['POST'])]
    public function runExpirationCheck(): JsonResponse
    {
        try {
            $results = $this->expirationService->deactivateExpiredGalleries();
            
            return $this->json([
                'success' => true,
                'message' => sprintf('Expiration check completed. %d galleries processed, %d deactivated.', 
                    $results['processed'], $results['deactivated']),
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to run expiration check: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/send-reminders', name: 'admin_gallery_expiration_send_reminders', methods: ['POST'])]
    public function sendReminders(): JsonResponse
    {
        try {
            $results = $this->expirationService->sendExpirationReminders();
            
            return $this->json([
                'success' => true,
                'message' => sprintf('Reminders sent successfully. %d reminders sent.', $results['reminders_sent']),
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to send reminders: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/cleanup-old', name: 'admin_gallery_expiration_cleanup', methods: ['POST'])]
    public function cleanupOldGalleries(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $daysOld = $data['days'] ?? 90;
            $results = $this->expirationService->cleanupOldExpiredGalleries($daysOld);
            
            return $this->json([
                'success' => true,
                'message' => sprintf('Cleanup completed. %d galleries processed, %d archived.', 
                    $results['processed'], $results['archived']),
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to cleanup old galleries: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/stats/api', name: 'admin_gallery_expiration_stats_api')]
    public function getStatsApi(): JsonResponse
    {
        $stats = $this->expirationService->getExpirationStats();
        return $this->json($stats);
    }

    #[Route('/refresh-stats', name: 'admin_gallery_expiration_refresh_stats', methods: ['POST'])]
    public function refreshStats(): JsonResponse
    {
        try {
            $stats = $this->expirationService->getExpirationStats();
            
            return $this->json([
                'success' => true,
                'total_with_expiration' => $stats['total_with_expiration'],
                'expired' => $stats['expired'],
                'expiring_1_day' => $stats['expiring_1_day'],
                'expiring_7_days' => $stats['expiring_7_days'],
                'expiring_30_days' => $stats['expiring_30_days']
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to refresh stats: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/tab-content', name: 'admin_gallery_expiration_tab_content')]
    public function getTabContent(Request $request): Response
    {
        $tab = $request->query->get('tab', 'expired');
        
        switch ($tab) {
            case 'expired':
                $galleries = $this->galleryRepository->findExpiredGalleries();
                break;
            case 'expiring-soon':
                $galleries = $this->galleryRepository->findGalleriesExpiringWithin(7);
                break;
            case 'all-with-expiration':
                $galleries = $this->galleryRepository->findGalleriesWithExpiration();
                break;
            default:
                $galleries = [];
        }

        return $this->render('admin/gallery_expiration/tab_content.html.twig', [
            'galleries' => $galleries,
            'tab' => $tab
        ]);
    }
}