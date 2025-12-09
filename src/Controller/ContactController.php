<?php

namespace App\Controller;

use App\Service\SiteConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class ContactController extends AbstractController
{
    public function __construct(
        private MailerInterface $mailer,
        private SiteConfigService $siteConfigService
    ) {
    }

    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request): Response
    {
        $clubInfo = $this->siteConfigService->getClubInfo();
        $submitted = false;
        $error = null;

        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name', ''));
            $email = trim($request->request->get('email', ''));
            $subject = trim($request->request->get('subject', ''));
            $message = trim($request->request->get('message', ''));
            $honeypot = $request->request->get('website', ''); // Champ anti-spam

            // Validation
            if (empty($name) || empty($email) || empty($message)) {
                $error = 'Veuillez remplir tous les champs obligatoires.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Veuillez saisir une adresse email valide.';
            } elseif (!empty($honeypot)) {
                // Bot détecté via honeypot, on simule un succès
                $submitted = true;
            } else {
                try {
                    // Préparer le sujet
                    $emailSubject = '[Contact Site] ';
                    $emailSubject .= $subject ?: 'Nouveau message de ' . $name;

                    // Créer l'email
                    $emailMessage = (new Email())
                        ->from($clubInfo['email'])
                        ->replyTo($email)
                        ->to($clubInfo['email'])
                        ->subject($emailSubject)
                        ->html($this->renderView('emails/contact.html.twig', [
                            'name' => $name,
                            'email' => $email,
                            'subject' => $subject,
                            'message' => $message,
                            'clubInfo' => $clubInfo,
                        ]));

                    $this->mailer->send($emailMessage);

                    // Email de confirmation à l'expéditeur
                    $confirmationEmail = (new Email())
                        ->from($clubInfo['email'])
                        ->to($email)
                        ->subject('Confirmation de votre message - ' . $clubInfo['name'])
                        ->html($this->renderView('emails/contact_confirmation.html.twig', [
                            'name' => $name,
                            'clubInfo' => $clubInfo,
                        ]));

                    $this->mailer->send($confirmationEmail);

                    $submitted = true;
                    $this->addFlash('success', 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.');

                } catch (\Exception $e) {
                    $error = 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer plus tard.';
                }
            }
        }

        return $this->render('public/contact.html.twig', [
            'clubInfo' => $clubInfo,
            'submitted' => $submitted,
            'error' => $error,
        ]);
    }
}
