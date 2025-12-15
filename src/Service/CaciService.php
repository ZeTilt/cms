<?php

namespace App\Service;

use App\Entity\CaciAccessLog;
use App\Entity\MedicalCertificate;
use App\Entity\User;
use App\Repository\MedicalCertificateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

class CaciService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CaciEncryptionService $encryptionService,
        private MedicalCertificateRepository $certificateRepository,
        private RequestStack $requestStack
    ) {}

    /**
     * Upload and store a new CACI for a user
     */
    public function uploadCaci(
        User $user,
        UploadedFile $file,
        \DateTimeInterface $expiryDate,
        bool $consentGiven
    ): MedicalCertificate {
        if (!$consentGiven) {
            throw new \InvalidArgumentException('Le consentement est requis pour télécharger un CACI');
        }

        // Validate file type
        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \InvalidArgumentException('Type de fichier non autorisé. Seuls PDF, JPEG, PNG et WebP sont acceptés.');
        }

        // Max file size: 5MB
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Le fichier est trop volumineux. Taille maximum: 5 Mo.');
        }

        // Encrypt and store
        $encryptedPath = $this->encryptionService->encryptAndStore($file, $user->getId());

        // Create certificate entity
        $certificate = new MedicalCertificate();
        $certificate->setUser($user)
            ->setEncryptedFilePath($encryptedPath)
            ->setOriginalFilename($file->getClientOriginalName())
            ->setExpiryDate($expiryDate)
            ->setConsentGiven(true);

        $this->entityManager->persist($certificate);
        $this->entityManager->flush();

        return $certificate;
    }

    /**
     * Get decrypted file content for viewing/downloading
     */
    public function getDecryptedContent(MedicalCertificate $certificate, User $accessedBy, string $action): string
    {
        // Log access
        $this->logAccess($accessedBy, $certificate->getUser(), $action, $certificate);

        return $this->encryptionService->decrypt($certificate->getEncryptedFilePath());
    }

    /**
     * Get MIME type of the stored file
     */
    public function getMimeType(MedicalCertificate $certificate): string
    {
        return $this->encryptionService->getMimeType($certificate->getEncryptedFilePath());
    }

    /**
     * Validate a certificate
     */
    public function validateCertificate(MedicalCertificate $certificate, User $validator): void
    {
        $certificate->validate($validator);

        $this->logAccess($validator, $certificate->getUser(), CaciAccessLog::ACTION_VALIDATE, $certificate);

        $this->entityManager->flush();
    }

    /**
     * Reject a certificate
     */
    public function rejectCertificate(MedicalCertificate $certificate, User $validator, string $reason): void
    {
        $certificate->reject($validator, $reason);

        $this->logAccess($validator, $certificate->getUser(), CaciAccessLog::ACTION_REJECT, $certificate);

        $this->entityManager->flush();
    }

    /**
     * Delete a certificate (and its encrypted file)
     */
    public function deleteCertificate(MedicalCertificate $certificate): void
    {
        // Delete encrypted file
        $this->encryptionService->delete($certificate->getEncryptedFilePath());

        // Remove from database
        $this->entityManager->remove($certificate);
        $this->entityManager->flush();
    }

    /**
     * Get current certificate for a user
     */
    public function getCurrentCertificate(User $user): ?MedicalCertificate
    {
        return $this->certificateRepository->findCurrentForUser($user);
    }

    /**
     * Get CACI status for a user (for profile display)
     */
    public function getCaciStatusForUser(User $user): array
    {
        $certificate = $this->getCurrentCertificate($user);

        if (!$certificate) {
            return [
                'status' => 'missing',
                'label' => 'Aucun CACI',
                'class' => 'badge-error',
                'canRegister' => false,
                'certificate' => null
            ];
        }

        if ($certificate->isExpired()) {
            return [
                'status' => 'expired',
                'label' => 'CACI expiré',
                'class' => 'badge-error',
                'canRegister' => false,
                'certificate' => $certificate
            ];
        }

        if ($certificate->isRejected()) {
            return [
                'status' => 'rejected',
                'label' => 'CACI rejeté',
                'class' => 'badge-error',
                'canRegister' => false,
                'certificate' => $certificate
            ];
        }

        if ($certificate->isPending()) {
            return [
                'status' => 'pending',
                'label' => 'En attente de validation',
                'class' => 'badge-warning',
                'canRegister' => true, // Pending = non-bloquant
                'certificate' => $certificate
            ];
        }

        return [
            'status' => 'valid',
            'label' => 'CACI validé',
            'class' => 'badge-success',
            'canRegister' => true,
            'certificate' => $certificate
        ];
    }

    /**
     * Log access for RGPD compliance
     */
    private function logAccess(User $accessedBy, User $targetUser, string $action, ?MedicalCertificate $certificate = null): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request?->getClientIp();

        $context = $this->determineContext($accessedBy);

        $log = CaciAccessLog::create(
            $accessedBy,
            $targetUser,
            $action,
            $context,
            $certificate,
            $ipAddress
        );

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * Determine the access context based on user role
     */
    private function determineContext(User $user): string
    {
        if (in_array('ROLE_CACI_REFERENT', $user->getRoles())) {
            return 'referent_validation';
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return 'admin_consultation';
        }

        if (in_array('ROLE_DP', $user->getRoles())) {
            return 'dp_event_check';
        }

        return 'self_access';
    }
}
