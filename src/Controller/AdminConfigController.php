<?php

namespace App\Controller;

use App\Service\SiteConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/config', name: 'admin_config_')]
#[IsGranted('ROLE_ADMIN')]
class AdminConfigController extends AbstractController
{
    public function __construct(
        private SiteConfigService $siteConfigService
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

        $this->siteConfigService->set('club_name', $clubName, 'Nom du club');
        $this->siteConfigService->set('club_address', $clubAddress, 'Adresse du club');
        $this->siteConfigService->set('club_phone', $clubPhone, 'Téléphone du club');
        $this->siteConfigService->set('club_email', $clubEmail, 'Email du club');
        $this->siteConfigService->set('club_facebook', $clubFacebook, 'Page Facebook du club');

        $this->addFlash('success', 'Configuration sauvegardée avec succès.');

        return $this->redirectToRoute('admin_config_index');
    }
}