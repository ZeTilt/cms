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

#[Route('/referent/caci')]
#[IsGranted('ROLE_CACI_REFERENT')]
class CaciReferentController extends AbstractController
{
    public function __construct(
        private CaciService $caciService,
        private MedicalCertificateRepository $certificateRepository
    ) {}

    /**
     * List all CACIs pending validation
     */
    #[Route('', name: 'referent_caci_list', methods: ['GET'])]
    public function index(): Response
    {
        $pendingCertificates = $this->certificateRepository->findPendingValidation();

        // Stats
        $stats = [
            'pending' => count($pendingCertificates),
            'expiring_soon' => count($this->certificateRepository->findExpiringSoon(30)),
        ];

        return $this->render('referent/caci/index.html.twig', [
            'pendingCertificates' => $pendingCertificates,
            'stats' => $stats,
        ]);
    }

    /**
     * View a CACI document for validation
     */
    #[Route('/{id}', name: 'referent_caci_view', methods: ['GET'])]
    public function view(MedicalCertificate $certificate): Response
    {
        $this->denyAccessUnlessGranted(CaciAccessVoter::VIEW, $certificate);

        return $this->render('referent/caci/view.html.twig', [
            'certificate' => $certificate,
        ]);
    }

    /**
     * Get the CACI file content for inline viewing
     */
    #[Route('/{id}/file', name: 'referent_caci_file', methods: ['GET'])]
    public function viewFile(MedicalCertificate $certificate): Response
    {
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
     * Validate a CACI
     */
    #[Route('/{id}/validate', name: 'referent_caci_validate', methods: ['POST'])]
    public function validate(Request $request, MedicalCertificate $certificate): Response
    {
        $this->denyAccessUnlessGranted(CaciAccessVoter::VALIDATE, $certificate);

        if (!$this->isCsrfTokenValid('caci_validate_' . $certificate->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('referent_caci_list');
        }

        if (!$certificate->isPending()) {
            $this->addFlash('error', 'Ce CACI a déjà été traité.');
            return $this->redirectToRoute('referent_caci_list');
        }

        /** @var User $user */
        $user = $this->getUser();

        $this->caciService->validateCertificate($certificate, $user);

        $this->addFlash('success', sprintf(
            'CACI de %s validé avec succès (valide jusqu\'au %s).',
            $certificate->getUser()->getFullName(),
            $certificate->getExpiryDate()->format('d/m/Y')
        ));

        return $this->redirectToRoute('referent_caci_list');
    }

    /**
     * Reject a CACI
     */
    #[Route('/{id}/reject', name: 'referent_caci_reject', methods: ['POST'])]
    public function reject(Request $request, MedicalCertificate $certificate): Response
    {
        $this->denyAccessUnlessGranted(CaciAccessVoter::VALIDATE, $certificate);

        if (!$this->isCsrfTokenValid('caci_reject_' . $certificate->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('referent_caci_list');
        }

        if (!$certificate->isPending()) {
            $this->addFlash('error', 'Ce CACI a déjà été traité.');
            return $this->redirectToRoute('referent_caci_list');
        }

        $reason = $request->request->get('reason', '');
        if (empty(trim($reason))) {
            $this->addFlash('error', 'Veuillez indiquer un motif de rejet.');
            return $this->redirectToRoute('referent_caci_view', ['id' => $certificate->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        $this->caciService->rejectCertificate($certificate, $user, $reason);

        $this->addFlash('warning', sprintf(
            'CACI de %s rejeté. L\'utilisateur sera notifié.',
            $certificate->getUser()->getFullName()
        ));

        return $this->redirectToRoute('referent_caci_list');
    }

    /**
     * Batch validate multiple CACIs
     */
    #[Route('/batch-validate', name: 'referent_caci_batch_validate', methods: ['POST'])]
    public function batchValidate(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('caci_batch_validate', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('referent_caci_list');
        }

        $certificateIds = $request->request->all('certificate_ids');

        if (empty($certificateIds)) {
            $this->addFlash('warning', 'Aucun CACI sélectionné.');
            return $this->redirectToRoute('referent_caci_list');
        }

        /** @var User $user */
        $user = $this->getUser();
        $validated = 0;
        $skipped = 0;

        foreach ($certificateIds as $id) {
            $certificate = $this->certificateRepository->find($id);

            if (!$certificate || !$certificate->isPending()) {
                $skipped++;
                continue;
            }

            $this->caciService->validateCertificate($certificate, $user);
            $validated++;
        }

        if ($validated > 0) {
            $this->addFlash('success', sprintf('%d CACI validé(s) avec succès.', $validated));
        }

        if ($skipped > 0) {
            $this->addFlash('warning', sprintf('%d CACI ignoré(s) (déjà traités).', $skipped));
        }

        return $this->redirectToRoute('referent_caci_list');
    }
}
