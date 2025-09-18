<?php

namespace App\Controller;

use App\Service\SiteConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/config', name: 'admin_config_')]
#[IsGranted('ROLE_ADMIN')]
class AdminConfigController extends AbstractController
{
    public function __construct(
        private SiteConfigService $siteConfigService,
        private SluggerInterface $slugger
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $clubInfo = $this->siteConfigService->getClubInfo();

        return $this->render('admin/config/index.html.twig', [
            'clubInfo' => $clubInfo,
        ]);
    }

    #[Route('/save', name: 'save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $clubName = $request->request->get('club_name');
        $clubAddress = $request->request->get('club_address');
        $clubPhone = $request->request->get('club_phone');
        $clubEmail = $request->request->get('club_email');
        $clubFacebook = $request->request->get('club_facebook');
        $helloassoUrl = $request->request->get('helloasso_url');

        $this->siteConfigService->set('club_name', $clubName, 'Nom du club');
        $this->siteConfigService->set('club_address', $clubAddress, 'Adresse du club');
        $this->siteConfigService->set('club_phone', $clubPhone, 'Téléphone du club');
        $this->siteConfigService->set('club_email', $clubEmail, 'Email du club');
        $this->siteConfigService->set('club_facebook', $clubFacebook, 'Page Facebook du club');
        $this->siteConfigService->set('helloasso_url', $helloassoUrl, 'Lien HelloAsso pour les adhésions');

        // Handle tarifs PDF upload
        $tarifsFile = $request->files->get('tarifs_pdf');
        if ($tarifsFile) {
            $originalFilename = pathinfo($tarifsFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$tarifsFile->guessExtension();

            try {
                $tarifsFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/documents',
                    $newFilename
                );
                
                // Remove old file if exists
                $currentTarifsPath = $this->siteConfigService->get('tarifs_pdf');
                if ($currentTarifsPath) {
                    $oldFilePath = $this->getParameter('kernel.project_dir').'/public'.$currentTarifsPath;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                
                $this->siteConfigService->set('tarifs_pdf', '/uploads/documents/'.$newFilename, 'Fichier PDF des tarifs');
                $this->addFlash('success', 'Fichier des tarifs uploadé avec succès.');
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload du fichier.');
            }
        }

        $this->addFlash('success', 'Configuration sauvegardée avec succès.');

        return $this->redirectToRoute('admin_config_index');
    }
}