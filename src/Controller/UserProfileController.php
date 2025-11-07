<?php

namespace App\Controller;

use App\Repository\DivingLevelRepository;
use App\Repository\FreedivingLevelRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class UserProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DivingLevelRepository $divingLevelRepository,
        private FreedivingLevelRepository $freedivingLevelRepository,
        private SluggerInterface $slugger,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    #[Route('', name: 'user_profile_index')]
    public function index(): Response
    {
        try {
            $user = $this->getUser();
            $divingLevels = $this->divingLevelRepository->findAllOrdered();
            $freedivingLevels = $this->freedivingLevelRepository->findAllOrdered();

            return $this->render('user/profile/index.html.twig', [
                'user' => $user,
                'divingLevels' => $divingLevels,
                'freedivingLevels' => $freedivingLevels,
            ]);
        } catch (\Exception $e) {
            // Debug temporaire
            return new Response('Erreur: ' . $e->getMessage());
        }
    }

    #[Route('/diving-level', name: 'user_profile_diving_level', methods: ['POST'])]
    public function updateDivingLevel(Request $request): Response
    {
        $user = $this->getUser();
        $divingLevelId = $request->request->get('diving_level_id');

        if ($divingLevelId === '') {
            // Vider le niveau de plongée
            $user->setHighestDivingLevel(null);
            $this->addFlash('success', 'Niveau de plongée supprimé de votre profil.');
        } elseif ($divingLevelId) {
            $divingLevel = $this->divingLevelRepository->find($divingLevelId);

            if (!$divingLevel || !$divingLevel->isActive()) {
                $this->addFlash('error', 'Niveau de plongée invalide.');
                return $this->redirectToRoute('user_profile_index');
            }

            $user->setHighestDivingLevel($divingLevel);
            $this->addFlash('success', 'Votre niveau de plongée a été mis à jour : ' . $divingLevel->getName());
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('user_profile_index');
    }

    #[Route('/medical-certificate', name: 'user_profile_medical_certificate', methods: ['POST'])]
    public function updateMedicalCertificate(Request $request): Response
    {
        // Validation du token CSRF
        $token = new CsrfToken('medical_certificate', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('user_profile_index');
        }

        $user = $this->getUser();

        // Gérer la date d'expiration
        $expiryDate = $request->request->get('medical_certificate_expiry');
        if ($expiryDate) {
            try {
                $user->setMedicalCertificateExpiry(new \DateTime($expiryDate));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Date d\'expiration invalide.');
                return $this->redirectToRoute('user_profile_index');
            }
        } else {
            $this->addFlash('error', 'La date d\'expiration est obligatoire.');
            return $this->redirectToRoute('user_profile_index');
        }

        // Gérer l'upload du fichier
        $file = $request->files->get('medical_certificate_file');
        if ($file) {
            // Vérifier la taille du fichier (max 5 Mo)
            if ($file->getSize() > 5 * 1024 * 1024) {
                $this->addFlash('error', 'Le fichier est trop volumineux (max 5 Mo).');
                return $this->redirectToRoute('user_profile_index');
            }

            // Vérifier le type de fichier
            $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                $this->addFlash('error', 'Format de fichier non autorisé. Utilisez PDF, JPG ou PNG.');
                return $this->redirectToRoute('user_profile_index');
            }

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $extension = $file->guessExtension();
            $newFilename = $user->getId() . '_' . uniqid() . '.' . $extension;

            try {
                // Créer le répertoire s'il n'existe pas
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/medical_certificates';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Supprimer l'ancien fichier si existe
                if ($user->getMedicalCertificateFile()) {
                    $oldFile = $uploadDir . '/' . $user->getMedicalCertificateFile();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                // Déplacer le nouveau fichier
                $file->move($uploadDir, $newFilename);
                $user->setMedicalCertificateFile($newFilename);

                $this->addFlash('success', 'Certificat médical enregistré avec succès !');
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload du fichier : ' . $e->getMessage());
                return $this->redirectToRoute('user_profile_index');
            }
        } elseif (!$user->getMedicalCertificateFile()) {
            // Si pas de fichier uploadé et pas de fichier existant
            $this->addFlash('warning', 'Date d\'expiration mise à jour. N\'oubliez pas d\'uploader votre certificat !');
        } else {
            // Juste mise à jour de la date, fichier déjà existant
            $this->addFlash('success', 'Date d\'expiration mise à jour.');
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('user_profile_index');
    }

    #[Route('/licence', name: 'user_profile_licence', methods: ['POST'])]
    public function updateLicence(Request $request): Response
    {
        // Validation du token CSRF
        $token = new CsrfToken('licence', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('user_profile_index');
        }

        $user = $this->getUser();

        // Gérer le numéro de licence
        $licenceNumber = $request->request->get('licence_number');
        if ($licenceNumber) {
            $user->setLicenceNumber($licenceNumber);
        }

        // Gérer la date d'expiration (optionnelle)
        $expiryDate = $request->request->get('licence_expiry');
        if ($expiryDate) {
            try {
                $user->setLicenceExpiry(new \DateTime($expiryDate));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Date d\'expiration invalide.');
                return $this->redirectToRoute('user_profile_index');
            }
        } else {
            $user->setLicenceExpiry(null);
        }

        // Gérer l'upload du fichier
        $file = $request->files->get('licence_file');
        if ($file) {
            // Vérifier la taille du fichier (max 5 Mo)
            if ($file->getSize() > 5 * 1024 * 1024) {
                $this->addFlash('error', 'Le fichier est trop volumineux (max 5 Mo).');
                return $this->redirectToRoute('user_profile_index');
            }

            // Vérifier le type de fichier
            $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                $this->addFlash('error', 'Format de fichier non autorisé. Utilisez PDF, JPG ou PNG.');
                return $this->redirectToRoute('user_profile_index');
            }

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $extension = $file->guessExtension();
            $newFilename = $user->getId() . '_' . uniqid() . '.' . $extension;

            try {
                // Créer le répertoire s'il n'existe pas
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/licences';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Supprimer l'ancien fichier si existe
                if ($user->getLicenceFile()) {
                    $oldFile = $uploadDir . '/' . $user->getLicenceFile();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                // Déplacer le nouveau fichier
                $file->move($uploadDir, $newFilename);
                $user->setLicenceFile($newFilename);

                $this->addFlash('success', 'Justificatif de licence enregistré avec succès !');
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload du fichier : ' . $e->getMessage());
                return $this->redirectToRoute('user_profile_index');
            }
        } elseif (!$user->getLicenceFile()) {
            // Si pas de fichier uploadé et pas de fichier existant
            $this->addFlash('warning', 'Date d\'expiration mise à jour. N\'oubliez pas d\'uploader votre justificatif !');
        } else {
            // Juste mise à jour de la date/numéro, fichier déjà existant
            $this->addFlash('success', 'Informations de licence mises à jour.');
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('user_profile_index');
    }

    #[Route('/personal-info', name: 'user_profile_personal_info', methods: ['POST'])]
    public function updatePersonalInfo(Request $request): Response
    {
        // Validation du token CSRF
        $token = new CsrfToken('personal_info', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('user_profile_index');
        }

        $user = $this->getUser();

        // Téléphone mobile
        $phoneNumber = $request->request->get('phone_number');
        $user->setPhoneNumber($phoneNumber ?: null);

        // Date de naissance
        $dateOfBirth = $request->request->get('date_of_birth');
        if ($dateOfBirth) {
            try {
                $user->setDateOfBirth(new \DateTime($dateOfBirth));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Date de naissance invalide.');
                return $this->redirectToRoute('user_profile_index');
            }
        } else {
            $user->setDateOfBirth(null);
        }

        // Adresse postale
        $address = $request->request->get('address');
        $user->setAddress($address ?: null);

        $this->entityManager->flush();

        $this->addFlash('success', 'Informations personnelles mises à jour avec succès !');

        return $this->redirectToRoute('user_profile_index');
    }

    #[Route('/activities', name: 'user_profile_activities', methods: ['POST'])]
    public function updateActivities(Request $request): Response
    {
        // Validation du token CSRF
        $token = new CsrfToken('profile_activities', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('user_profile_index');
        }

        $user = $this->getUser();

        // Mise à jour des cases à cocher d'activités
        $user->setDiver((bool) $request->request->get('is_diver'));
        $user->setFreediver((bool) $request->request->get('is_freediver'));
        $user->setPilot((bool) $request->request->get('is_pilot'));

        // Mise à jour du niveau de plongée bouteille
        if ($user->isDiver()) {
            $divingLevelId = $request->request->get('diving_level_id');
            if ($divingLevelId === '') {
                $user->setHighestDivingLevel(null);
            } elseif ($divingLevelId) {
                $divingLevel = $this->divingLevelRepository->find($divingLevelId);
                if ($divingLevel && $divingLevel->isActive()) {
                    $user->setHighestDivingLevel($divingLevel);
                }
            }
        } else {
            // Si l'utilisateur ne pratique plus la plongée bouteille, on enlève le niveau
            $user->setHighestDivingLevel(null);
        }

        // Mise à jour du niveau d'apnée
        if ($user->isFreediver()) {
            $freedivingLevelId = $request->request->get('freediving_level_id');
            if ($freedivingLevelId === '') {
                $user->setHighestFreedivingLevel(null);
            } elseif ($freedivingLevelId) {
                $freedivingLevel = $this->freedivingLevelRepository->find($freedivingLevelId);
                if ($freedivingLevel && $freedivingLevel->isActive()) {
                    $user->setHighestFreedivingLevel($freedivingLevel);
                }
            }
        } else {
            // Si l'utilisateur ne pratique plus l'apnée, on enlève le niveau
            $user->setHighestFreedivingLevel(null);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Vos activités et niveaux ont été mis à jour avec succès !');

        return $this->redirectToRoute('user_profile_index');
    }

    #[Route('/upload-avatar', name: 'user_profile_upload_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request): Response
    {
        // Validation du token CSRF
        $token = new CsrfToken('upload_avatar', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('user_profile_index');
        }

        $user = $this->getUser();
        $file = $request->files->get('avatar');

        if (!$file) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');
            return $this->redirectToRoute('user_profile_index');
        }

        // Vérifier la taille du fichier (max 2 Mo pour un avatar)
        if ($file->getSize() > 2 * 1024 * 1024) {
            $this->addFlash('error', 'Le fichier est trop volumineux (max 2 Mo).');
            return $this->redirectToRoute('user_profile_index');
        }

        // Vérifier le type de fichier (uniquement images)
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            $this->addFlash('error', 'Format de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
            return $this->redirectToRoute('user_profile_index');
        }

        $extension = $file->guessExtension();
        $newFilename = $user->getId() . '_' . uniqid() . '.' . $extension;

        try {
            // Créer le répertoire s'il n'existe pas
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Supprimer l'ancien avatar si existe
            if ($user->getAvatarFile()) {
                $oldFile = $uploadDir . '/' . $user->getAvatarFile();
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            // Déplacer le nouveau fichier
            $file->move($uploadDir, $newFilename);
            $user->setAvatarFile($newFilename);

            $this->entityManager->flush();

            $this->addFlash('success', 'Avatar mis à jour avec succès !');
        } catch (FileException $e) {
            $this->addFlash('error', 'Erreur lors de l\'upload du fichier : ' . $e->getMessage());
            return $this->redirectToRoute('user_profile_index');
        }

        return $this->redirectToRoute('user_profile_index');
    }

    #[Route('/delete-avatar', name: 'user_profile_delete_avatar', methods: ['POST'])]
    public function deleteAvatar(Request $request): Response
    {
        // Validation du token CSRF
        $token = new CsrfToken('delete_avatar', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('user_profile_index');
        }

        $user = $this->getUser();

        if (!$user->getAvatarFile()) {
            $this->addFlash('warning', 'Aucun avatar à supprimer.');
            return $this->redirectToRoute('user_profile_index');
        }

        try {
            // Supprimer le fichier du système de fichiers
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
            $avatarPath = $uploadDir . '/' . $user->getAvatarFile();

            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }

            // Supprimer la référence dans la base de données
            $user->setAvatarFile(null);
            $this->entityManager->flush();

            $this->addFlash('success', 'Avatar supprimé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression de l\'avatar : ' . $e->getMessage());
        }

        return $this->redirectToRoute('user_profile_index');
    }

    #[Route('/remind-admins', name: 'user_profile_remind_admins', methods: ['POST'])]
    public function remindAdmins(Request $request): Response
    {
        // Validation du token CSRF
        $token = new CsrfToken('remind_admins', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('user_profile_index');
        }

        $user = $this->getUser();

        // Vérifier que le compte est bien en attente
        if ($user->getStatus() !== 'pending') {
            $this->addFlash('info', 'Votre compte a déjà été traité par les administrateurs.');
            return $this->redirectToRoute('user_profile_index');
        }

        // Récupérer tous les administrateurs
        $admins = $this->userRepository->findAdmins();

        if (empty($admins)) {
            $this->addFlash('error', 'Aucun administrateur trouvé. Contactez-nous directement.');
            return $this->redirectToRoute('user_profile_index');
        }

        // Envoyer un email à chaque administrateur
        $emailsSent = 0;
        foreach ($admins as $admin) {
            try {
                $email = (new TemplatedEmail())
                    ->from('noreply@plongee-venetes.fr')
                    ->to($admin->getEmail())
                    ->subject('Rappel : Nouveau membre en attente de validation')
                    ->htmlTemplate('emails/admin_reminder.html.twig')
                    ->context([
                        'admin' => $admin,
                        'pendingUser' => $user,
                        'adminPanelUrl' => $this->generateUrl('admin_users_index', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL)
                    ]);

                $this->mailer->send($email);
                $emailsSent++;
            } catch (\Exception $e) {
                // Log l'erreur mais continue pour les autres admins
                error_log('Erreur envoi email admin: ' . $e->getMessage());
            }
        }

        if ($emailsSent > 0) {
            $this->addFlash('success', 'Votre demande a été envoyée aux administrateurs. Vous recevrez une notification dès que votre compte sera validé.');
        } else {
            $this->addFlash('error', 'Erreur lors de l\'envoi de la relance. Veuillez réessayer plus tard.');
        }

        return $this->redirectToRoute('user_profile_index');
    }
}