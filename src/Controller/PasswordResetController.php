<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * Page pour demander la réinitialisation
     */
    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password')]
    public function request(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Générer un token
                $token = bin2hex(random_bytes(32));
                $user->setResetPasswordToken($token);
                $user->setResetPasswordTokenExpiry(new \DateTimeImmutable('+1 hour'));
                $this->entityManager->flush();

                // Envoyer l'email
                $resetUrl = $this->generateUrl('app_reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                try {
                    $email = (new TemplatedEmail())
                        ->from('noreply@plongee-venetes.fr')
                        ->to($user->getEmail())
                        ->subject('Réinitialisation de votre mot de passe')
                        ->htmlTemplate('emails/password_reset.html.twig')
                        ->context([
                            'user' => $user,
                            'resetUrl' => $resetUrl,
                            'expiryTime' => $user->getResetPasswordTokenExpiry()
                        ]);

                    $this->mailer->send($email);
                } catch (\Exception $e) {
                    // Log l'erreur mais ne révèle pas qu'un email existe
                }
            }

            // Toujours afficher le même message (sécurité)
            $this->addFlash('success', 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    /**
     * Page pour réinitialiser le mot de passe avec le token
     */
    #[Route('/reinitialiser-mot-de-passe/{token}', name: 'app_reset_password')]
    public function reset(string $token, Request $request): Response
    {
        $user = $this->userRepository->findOneBy(['resetPasswordToken' => $token]);

        if (!$user || !$user->isResetPasswordTokenValid()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } elseif (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            } else {
                // Mettre à jour le mot de passe
                $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);

                // Invalider le token
                $user->setResetPasswordToken(null);
                $user->setResetPasswordTokenExpiry(null);

                $this->entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token
        ]);
    }
}
