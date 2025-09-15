<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\DivingLevel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer
    ) {}

    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request): Response
    {
        // Récupérer les niveaux de plongée actifs
        $divingLevels = $this->entityManager->getRepository(DivingLevel::class)
            ->findBy(['isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC']);

        if ($request->isMethod('POST')) {
            // Vérifier que l'email n'existe pas déjà
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $request->request->get('email')]);

            if ($existingUser) {
                $this->addFlash('error', 'Un compte avec cet email existe déjà.');
                return $this->redirectToRoute('app_register');
            }

            // Créer le nouvel utilisateur
            $user = new User();
            $user->setEmail($request->request->get('email'));
            $user->setFirstName($request->request->get('firstName'));
            $user->setLastName($request->request->get('lastName'));
            $user->setStatus('pending'); // En attente de validation
            $user->setActive(false); // Inactif jusqu'à validation
            $user->setEmailVerified(false); // Email non vérifié

            // Générer le token de vérification
            $token = $user->generateEmailVerificationToken();

            // Hasher le mot de passe
            $plainPassword = $request->request->get('password');
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Sauvegarder les attributs EAV si fournis
            if ($request->request->get('diving_level')) {
                $this->saveDivingLevel($user, $request->request->get('diving_level'));
            }
            
            if ($request->request->get('is_freediver')) {
                $this->saveFreediverStatus($user, true);
            }

            $this->entityManager->flush();

            // Envoyer l'email de vérification
            $this->sendVerificationEmail($user);

            $this->addFlash('success', 'Inscription réussie ! Vérifiez votre boîte email pour confirmer votre adresse.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'diving_levels' => $divingLevels,
        ]);
    }

    private function saveDivingLevel(User $user, ?string $divingLevel): void
    {
        if (!$divingLevel) return;

        $attribute = new \App\Entity\EntityAttribute();
        $attribute->setEntityType('User');
        $attribute->setEntityId($user->getId());
        $attribute->setAttributeName('diving_level');
        $attribute->setAttributeType('text');
        $attribute->setAttributeValue($divingLevel);
        
        $this->entityManager->persist($attribute);
    }

    private function saveFreediverStatus(User $user, bool $isFreediver): void
    {
        if (!$isFreediver) return;

        $attribute = new \App\Entity\EntityAttribute();
        $attribute->setEntityType('User');
        $attribute->setEntityId($user->getId());
        $attribute->setAttributeName('is_freediver');
        $attribute->setAttributeType('boolean');
        $attribute->setAttributeValue('1');
        
        $this->entityManager->persist($attribute);
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token): Response
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Token de vérification invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier l'email
        $user->setEmailVerified(true);
        $user->setEmailVerificationToken(null); // Supprimer le token après utilisation
        $this->entityManager->flush();

        $this->addFlash('success', 'Adresse email vérifiée ! Votre compte est maintenant en attente de validation par un administrateur.');
        return $this->redirectToRoute('app_login');
    }

    private function sendVerificationEmail(User $user): void
    {
        $verificationUrl = $this->generateUrl('app_verify_email', 
            ['token' => $user->getEmailVerificationToken()], 
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from('noreply@club-venetes.fr')
            ->to($user->getEmail())
            ->subject('Vérification de votre adresse email - Club Subaquatique des Vénètes')
            ->html($this->renderView('emails/verify_email.html.twig', [
                'user' => $user,
                'verification_url' => $verificationUrl
            ]));

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas bloquer l'inscription
            // En production, vous voudriez logger cela proprement
        }
    }
}