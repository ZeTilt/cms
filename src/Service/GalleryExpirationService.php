<?php

namespace App\Service;

use App\Entity\Gallery;
use App\Repository\GalleryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Service\NotificationService;

class GalleryExpirationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GalleryRepository $galleryRepository,
        private LoggerInterface $logger,
        private NotificationService $notificationService
    ) {}

    /**
     * Désactiver toutes les galeries expirées
     */
    public function deactivateExpiredGalleries(): array
    {
        $results = [
            'processed' => 0,
            'deactivated' => 0,
            'errors' => []
        ];

        try {
            // Récupérer toutes les galeries avec une date d'expiration
            $galleries = $this->galleryRepository->findGalleriesWithExpiration();
            $results['processed'] = count($galleries);

            foreach ($galleries as $gallery) {
                if ($gallery->isExpired()) {
                    try {
                        $this->deactivateGallery($gallery);
                        $results['deactivated']++;
                        
                        // Send expiration notification
                        $this->notificationService->sendGalleryExpiredNotification($gallery);
                        
                        $this->logger->info('Gallery deactivated due to expiration', [
                            'gallery_id' => $gallery->getId(),
                            'title' => $gallery->getTitle(),
                            'end_date' => $gallery->getEndDate()?->format('Y-m-d H:i:s')
                        ]);
                    } catch (\Exception $e) {
                        $error = "Failed to deactivate gallery {$gallery->getId()}: " . $e->getMessage();
                        $results['errors'][] = $error;
                        $this->logger->error($error, ['exception' => $e]);
                    }
                }
            }

            $this->entityManager->flush();

        } catch (\Exception $e) {
            $error = "Failed to process gallery expiration: " . $e->getMessage();
            $results['errors'][] = $error;
            $this->logger->error($error, ['exception' => $e]);
        }

        return $results;
    }

    /**
     * Obtenir les galeries qui expirent bientôt (dans les N prochains jours)
     */
    public function getGalleriesExpiringWithin(int $days): array
    {
        return $this->galleryRepository->findGalleriesExpiringWithin($days);
    }

    /**
     * Obtenir les statistiques d'expiration
     */
    public function getExpirationStats(): array
    {
        $now = new \DateTimeImmutable();
        
        return [
            'total_with_expiration' => $this->galleryRepository->countGalleriesWithExpiration(),
            'expired' => $this->galleryRepository->countExpiredGalleries(),
            'expiring_1_day' => count($this->getGalleriesExpiringWithin(1)),
            'expiring_7_days' => count($this->getGalleriesExpiringWithin(7)),
            'expiring_30_days' => count($this->getGalleriesExpiringWithin(30)),
            'checked_at' => $now->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Désactiver une galerie spécifique
     */
    private function deactivateGallery(Gallery $gallery): void
    {
        // Marquer comme expirée dans les métadonnées
        $metadata = $gallery->getMetadata() ?? [];
        $metadata['expired_at'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $metadata['auto_deactivated'] = true;
        $gallery->setMetadata($metadata);

        // Changer la visibilité pour empêcher l'accès
        $gallery->setVisibility('expired');

        // Révoquer tous les codes d'accès
        $gallery->setAccessCode(null);
        
        $this->entityManager->persist($gallery);
    }

    /**
     * Réactiver une galerie (enlever l'expiration)
     */
    public function reactivateGallery(Gallery $gallery, ?int $newDurationDays = null): void
    {
        // Enlever les métadonnées d'expiration
        $metadata = $gallery->getMetadata() ?? [];
        unset($metadata['expired_at'], $metadata['auto_deactivated']);
        $gallery->setMetadata($metadata);

        // Restaurer la visibilité par défaut
        if ($gallery->getVisibility() === 'expired') {
            $gallery->setVisibility('private'); // Par défaut les galeries expirées étaient probablement privées
        }

        // Définir une nouvelle durée si fournie
        if ($newDurationDays !== null) {
            $gallery->setDurationDays($newDurationDays);
        } else {
            // Enlever complètement l'expiration
            $gallery->setDurationDays(null);
            $gallery->setEndDate(null);
        }

        $this->entityManager->persist($gallery);
        $this->entityManager->flush();

        $this->logger->info('Gallery reactivated', [
            'gallery_id' => $gallery->getId(),
            'title' => $gallery->getTitle(),
            'new_duration' => $newDurationDays
        ]);
    }

    /**
     * Nettoyer les galeries expirées depuis longtemps (archivage)
     */
    public function cleanupOldExpiredGalleries(int $daysOld = 90): array
    {
        $cutoffDate = new \DateTimeImmutable("-{$daysOld} days");
        $results = [
            'processed' => 0,
            'archived' => 0,
            'errors' => []
        ];

        try {
            $oldExpiredGalleries = $this->galleryRepository->findOldExpiredGalleries($cutoffDate);
            $results['processed'] = count($oldExpiredGalleries);

            foreach ($oldExpiredGalleries as $gallery) {
                try {
                    $this->archiveGallery($gallery);
                    $results['archived']++;
                    
                    $this->logger->info('Old expired gallery archived', [
                        'gallery_id' => $gallery->getId(),
                        'title' => $gallery->getTitle(),
                        'end_date' => $gallery->getEndDate()?->format('Y-m-d H:i:s')
                    ]);
                } catch (\Exception $e) {
                    $error = "Failed to archive gallery {$gallery->getId()}: " . $e->getMessage();
                    $results['errors'][] = $error;
                    $this->logger->error($error, ['exception' => $e]);
                }
            }

            $this->entityManager->flush();

        } catch (\Exception $e) {
            $error = "Failed to cleanup old expired galleries: " . $e->getMessage();
            $results['errors'][] = $error;
            $this->logger->error($error, ['exception' => $e]);
        }

        return $results;
    }

    /**
     * Archiver une galerie (marquer comme archivée sans supprimer)
     */
    private function archiveGallery(Gallery $gallery): void
    {
        $metadata = $gallery->getMetadata() ?? [];
        $metadata['archived_at'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $metadata['auto_archived'] = true;
        $gallery->setMetadata($metadata);

        $gallery->setVisibility('archived');
        
        $this->entityManager->persist($gallery);
    }

    /**
     * Envoyer des rappels pour les galeries qui expirent bientôt
     */
    public function sendExpirationReminders(): array
    {
        $results = [
            'reminders_sent' => 0,
            'errors' => []
        ];

        // Galeries expirant dans 7 jours
        $expiring7Days = $this->getGalleriesExpiringWithin(7);
        
        // Galeries expirant dans 1 jour
        $expiring1Day = $this->getGalleriesExpiringWithin(1);

        // Envoyer les rappels pour les galeries expirant dans 7 jours
        foreach ($expiring7Days as $gallery) {
            try {
                $daysUntilExpiration = $gallery->getDaysUntilExpiration();
                if ($this->notificationService->sendGalleryExpirationReminder($gallery, $daysUntilExpiration)) {
                    $results['reminders_sent']++;
                }
                
                $this->logger->info('Gallery expiring in 7 days reminder sent', [
                    'gallery_id' => $gallery->getId(),
                    'title' => $gallery->getTitle(),
                    'end_date' => $gallery->getEndDate()?->format('Y-m-d H:i:s'),
                    'author_email' => $gallery->getAuthor()?->getEmail()
                ]);
            } catch (\Exception $e) {
                $error = "Failed to send 7-day reminder for gallery {$gallery->getId()}: " . $e->getMessage();
                $results['errors'][] = $error;
                $this->logger->error($error, ['exception' => $e]);
            }
        }

        // Envoyer les rappels pour les galeries expirant dans 1 jour
        foreach ($expiring1Day as $gallery) {
            try {
                $daysUntilExpiration = $gallery->getDaysUntilExpiration();
                if ($this->notificationService->sendGalleryExpirationReminder($gallery, $daysUntilExpiration)) {
                    $results['reminders_sent']++;
                }
                
                $this->logger->info('Gallery expiring in 1 day reminder sent', [
                    'gallery_id' => $gallery->getId(),
                    'title' => $gallery->getTitle(),
                    'end_date' => $gallery->getEndDate()?->format('Y-m-d H:i:s'),
                    'author_email' => $gallery->getAuthor()?->getEmail()
                ]);
            } catch (\Exception $e) {
                $error = "Failed to send 1-day reminder for gallery {$gallery->getId()}: " . $e->getMessage();
                $results['errors'][] = $error;
                $this->logger->error($error, ['exception' => $e]);
            }
        }

        return $results;
    }
}