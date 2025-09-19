<?php

namespace App\Controller;

use App\Service\SiteConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class DocumentController extends AbstractController
{
    public function __construct(
        private SiteConfigService $siteConfigService
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
}