<?php

namespace App\Service;

use App\Entity\Gallery;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private string $senderEmail = 'noreply@zetilt-cms.com',
        private string $senderName = 'ZeTilt CMS'
    ) {}

    /**
     * Send gallery expiration reminder email
     */
    public function sendGalleryExpirationReminder(Gallery $gallery, int $daysUntilExpiration): bool
    {
        try {
            $author = $gallery->getAuthor();
            if (!$author || !$author->getEmail()) {
                $this->logger->warning('Cannot send expiration reminder: no author email', [
                    'gallery_id' => $gallery->getId()
                ]);
                return false;
            }

            $subject = $this->translator->trans('email.gallery_expiration.subject', [
                'days' => $daysUntilExpiration,
                'title' => $gallery->getTitle()
            ], 'notifications');

            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($author->getEmail())
                ->subject($subject)
                ->htmlTemplate('emails/gallery_expiration_reminder.html.twig')
                ->context([
                    'gallery' => $gallery,
                    'author' => $author,
                    'days_until_expiration' => $daysUntilExpiration,
                    'expiration_date' => $gallery->getEndDate()
                ]);

            $this->mailer->send($email);

            $this->logger->info('Gallery expiration reminder sent', [
                'gallery_id' => $gallery->getId(),
                'author_email' => $author->getEmail(),
                'days_until_expiration' => $daysUntilExpiration
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send gallery expiration reminder', [
                'gallery_id' => $gallery->getId(),
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Send gallery expired notification
     */
    public function sendGalleryExpiredNotification(Gallery $gallery): bool
    {
        try {
            $author = $gallery->getAuthor();
            if (!$author || !$author->getEmail()) {
                return false;
            }

            $subject = $this->translator->trans('email.gallery_expired.subject', [
                'title' => $gallery->getTitle()
            ], 'notifications');

            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($author->getEmail())
                ->subject($subject)
                ->htmlTemplate('emails/gallery_expired.html.twig')
                ->context([
                    'gallery' => $gallery,
                    'author' => $author,
                    'expired_date' => $gallery->getEndDate()
                ]);

            $this->mailer->send($email);

            $this->logger->info('Gallery expired notification sent', [
                'gallery_id' => $gallery->getId(),
                'author_email' => $author->getEmail()
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send gallery expired notification', [
                'gallery_id' => $gallery->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail(User $user): bool
    {
        try {
            if (!$user->getEmail()) {
                return false;
            }

            $subject = $this->translator->trans('email.welcome.subject', [], 'notifications');

            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($user->getEmail())
                ->subject($subject)
                ->htmlTemplate('emails/welcome.html.twig')
                ->context([
                    'user' => $user
                ]);

            $this->mailer->send($email);

            $this->logger->info('Welcome email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send welcome email', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(User $user, string $resetToken): bool
    {
        try {
            if (!$user->getEmail()) {
                return false;
            }

            $subject = $this->translator->trans('email.password_reset.subject', [], 'notifications');

            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($user->getEmail())
                ->subject($subject)
                ->htmlTemplate('emails/password_reset.html.twig')
                ->context([
                    'user' => $user,
                    'reset_token' => $resetToken
                ]);

            $this->mailer->send($email);

            $this->logger->info('Password reset email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send password reset email', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification for payment success
     */
    public function sendPaymentSuccessNotification(User $user, array $paymentData): bool
    {
        try {
            if (!$user->getEmail()) {
                return false;
            }

            $subject = $this->translator->trans('email.payment_success.subject', [
                'amount' => $paymentData['amount'] ?? 'N/A'
            ], 'notifications');

            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($user->getEmail())
                ->subject($subject)
                ->htmlTemplate('emails/payment_success.html.twig')
                ->context([
                    'user' => $user,
                    'payment' => $paymentData
                ]);

            $this->mailer->send($email);

            $this->logger->info('Payment success notification sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'payment_id' => $paymentData['id'] ?? 'N/A'
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send payment success notification', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send event registration confirmation
     */
    public function sendEventRegistrationConfirmation(User $user, array $eventData): bool
    {
        try {
            if (!$user->getEmail()) {
                return false;
            }

            $subject = $this->translator->trans('email.event_registration.subject', [
                'event_title' => $eventData['title'] ?? 'Event'
            ], 'notifications');

            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($user->getEmail())
                ->subject($subject)
                ->htmlTemplate('emails/event_registration.html.twig')
                ->context([
                    'user' => $user,
                    'event' => $eventData
                ]);

            $this->mailer->send($email);

            $this->logger->info('Event registration confirmation sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'event_id' => $eventData['id'] ?? 'N/A'
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send event registration confirmation', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send bulk notifications
     */
    public function sendBulkNotifications(array $recipients, string $subject, string $template, array $context = []): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($recipients as $recipient) {
            try {
                $email = (new TemplatedEmail())
                    ->from(new Address($this->senderEmail, $this->senderName))
                    ->to($recipient['email'])
                    ->subject($subject)
                    ->htmlTemplate($template)
                    ->context(array_merge($context, ['recipient' => $recipient]));

                $this->mailer->send($email);
                $results['sent']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'email' => $recipient['email'],
                    'error' => $e->getMessage()
                ];

                $this->logger->error('Failed to send bulk notification', [
                    'email' => $recipient['email'],
                    'template' => $template,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('Bulk notifications sent', [
            'total_recipients' => count($recipients),
            'sent' => $results['sent'],
            'failed' => $results['failed'],
            'template' => $template
        ]);

        return $results;
    }
}