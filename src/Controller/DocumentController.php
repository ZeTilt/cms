<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SiteConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DocumentController extends AbstractController
{
    public function __construct(
        private SiteConfigService $siteConfigService,
        private UserRepository $userRepository
    ) {}

    #[Route('/documents/tarifs-pdf', name: 'download_tarifs_pdf')]
    public function downloadTarifsPdf(): Response
    {
        $pdfPath = $this->siteConfigService->get('tarifs_pdf');

        if (!$pdfPath) {
            throw $this->createNotFoundException('Le fichier PDF des tarifs n\'est pas configuré.');
        }

        $fullPath = $this->getParameter('kernel.project_dir') . '/public' . $pdfPath;

        if (!file_exists($fullPath)) {
            throw $this->createNotFoundException('Le fichier PDF des tarifs est introuvable.');
        }

        // Créer la réponse avec le fichier
        $response = new BinaryFileResponse($fullPath);

        // Définir le nom de fichier pour le téléchargement
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'Tarifs-CSV-2025.pdf'
        );

        return $response;
    }

    #[Route('/documents/medical-certificate/{userId}', name: 'download_medical_certificate')]
    #[IsGranted('ROLE_USER')]
    public function downloadMedicalCertificate(int $userId): Response
    {
        $currentUser = $this->getUser();
        $targetUser = $this->userRepository->find($userId);

        if (!$targetUser) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        // Vérifier les permissions : admin ou DP d'un événement avec ce participant
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles()) || in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());
        $canAccess = $isAdmin || $currentUser->getId() === $userId;

        if (!$canAccess) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce document.');
        }

        $medicalCertFile = $targetUser->getMedicalCertificateFile();

        if (!$medicalCertFile) {
            throw $this->createNotFoundException('Aucun certificat médical n\'est disponible pour cet utilisateur.');
        }

        // Le chemin des certificats médicaux
        $fullPath = $this->getParameter('kernel.project_dir') . '/public/uploads/medical_certificates/' . $medicalCertFile;

        if (!file_exists($fullPath)) {
            throw $this->createNotFoundException('Le fichier du certificat médical est introuvable.');
        }

        // Créer la réponse avec le fichier
        $response = new BinaryFileResponse($fullPath);

        // Définir le nom de fichier pour le téléchargement
        $filename = sprintf(
            'certificat_medical_%s_%s.pdf',
            preg_replace('/[^a-zA-Z0-9]/', '_', $targetUser->getFullName()),
            $targetUser->getMedicalCertificateExpiry() ? $targetUser->getMedicalCertificateExpiry()->format('Y-m-d') : 'date_inconnue'
        );

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE, // INLINE pour afficher dans le navigateur
            $filename
        );

        return $response;
    }
}