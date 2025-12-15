<?php

namespace App\Controller;

use App\Entity\MedicalCertificate;
use App\Entity\User;
use App\Repository\MedicalCertificateRepository;
use App\Security\Voter\CaciAccessVoter;
use App\Service\CaciService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-caci')]
#[IsGranted('ROLE_USER')]
class MemberCaciController extends AbstractController
{
    public function __construct(
        private CaciService $caciService,
        private MedicalCertificateRepository $certificateRepository
    ) {}

    /**
     * Redirect to profile page (CACI is now integrated there)
     */
    #[Route('', name: 'member_caci_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('user_profile_index');
    }

    /**
     * Upload a new CACI
     */
    #[Route('/upload', name: 'member_caci_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('caci_upload', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('user_profile_index');
        }

        $file = $request->files->get('caci_file');
        $expiryDateStr = $request->request->get('expiry_date');
        $consent = $request->request->getBoolean('consent');

        if (!$file) {
            $this->addFlash('error', 'Veuillez sélectionner un fichier.');
            return $this->redirectToRoute('user_profile_index');
        }

        if (!$expiryDateStr) {
            $this->addFlash('error', 'Veuillez indiquer la date d\'expiration.');
            return $this->redirectToRoute('user_profile_index');
        }

        if (!$consent) {
            $this->addFlash('error', 'Vous devez accepter les conditions de traitement des données.');
            return $this->redirectToRoute('user_profile_index');
        }

        try {
            $expiryDate = new \DateTime($expiryDateStr);

            // Validate expiry date is in the future
            if ($expiryDate < new \DateTime('today')) {
                $this->addFlash('error', 'La date d\'expiration doit être dans le futur.');
                return $this->redirectToRoute('user_profile_index');
            }

            $this->caciService->uploadCaci($user, $file, $expiryDate, $consent);

            $this->addFlash('success', 'Votre CACI a été téléchargé avec succès. Il sera validé par un référent CACI.');

        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du téléchargement.');
        }

        return $this->redirectToRoute('user_profile_index');
    }

    /**
     * View a CACI document
     */
    #[Route('/{id}/view', name: 'member_caci_view', methods: ['GET'])]
    public function view(MedicalCertificate $certificate): Response
    {
        // Check access via voter
        $this->denyAccessUnlessGranted(CaciAccessVoter::VIEW, $certificate);

        /** @var User $user */
        $user = $this->getUser();

        $content = $this->caciService->getDecryptedContent($certificate, $user, 'view');
        $mimeType = $this->caciService->getMimeType($certificate);

        return new Response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $certificate->getOriginalFilename() . '"',
        ]);
    }

    /**
     * Download a CACI document
     */
    #[Route('/{id}/download', name: 'member_caci_download', methods: ['GET'])]
    public function download(MedicalCertificate $certificate): Response
    {
        // Check access via voter
        $this->denyAccessUnlessGranted(CaciAccessVoter::DOWNLOAD, $certificate);

        /** @var User $user */
        $user = $this->getUser();

        $content = $this->caciService->getDecryptedContent($certificate, $user, 'download');
        $mimeType = $this->caciService->getMimeType($certificate);

        return new Response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $certificate->getOriginalFilename() . '"',
        ]);
    }

    /**
     * Delete a CACI (only own pending certificates)
     */
    #[Route('/{id}/delete', name: 'member_caci_delete', methods: ['POST'])]
    public function delete(Request $request, MedicalCertificate $certificate): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Only allow deletion of own pending certificates
        if ($certificate->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$certificate->isPending()) {
            $this->addFlash('error', 'Seuls les CACI en attente peuvent être supprimés.');
            return $this->redirectToRoute('user_profile_index');
        }

        if (!$this->isCsrfTokenValid('caci_delete_' . $certificate->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('user_profile_index');
        }

        $this->caciService->deleteCertificate($certificate);
        $this->addFlash('success', 'Le CACI a été supprimé.');

        return $this->redirectToRoute('user_profile_index');
    }
}
