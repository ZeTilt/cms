<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Service\EventRegistrationValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/events')]
#[IsGranted('ROLE_USER')]
class EventRegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRegistrationValidator $registrationValidator
    ) {}

    #[Route('/{id}/register', name: 'event_registration_form', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function registrationForm(Event $event): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur peut s'inscrire
        $validationErrors = $this->registrationValidator->validateUserForEvent($user, $event);
        
        return $this->render('events/register.html.twig', [
            'event' => $event,
            'user' => $user,
            'validation_errors' => $validationErrors,
            'can_register' => empty($validationErrors)
        ]);
    }

    #[Route('/{id}/register', name: 'event_registration_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function submitRegistration(Event $event, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('event_registration_' . $event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('event_registration_form', ['id' => $event->getId()]);
        }

        $user = $this->getUser();
        
        try {
            $options = [
                'numberOfSpots' => max(1, (int)$request->request->get('number_of_spots', 1)),
                'departureLocation' => $request->request->get('departure_location', 'club'),
                'comment' => $request->request->get('comment', '')
            ];

            $registration = $this->registrationValidator->registerUserForEvent($user, $event, $options);

            if ($registration->getStatus() === 'waiting_list') {
                $this->addFlash('warning', 'Vous avez été placé sur la liste d\'attente car l\'événement est complet.');
            } else {
                $this->addFlash('success', 'Votre inscription a été confirmée !');
            }

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);

        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('event_registration_form', ['id' => $event->getId()]);
        }
    }

    #[Route('/{id}/unregister', name: 'event_unregister_by_id', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unregister(Event $event, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('event_unregister_' . $event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $user = $this->getUser();
        $registration = $event->getUserRegistration($user);

        if (!$registration) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à cet événement.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        // Marquer comme annulé au lieu de supprimer
        $registration->setStatus('cancelled');
        $this->entityManager->flush();

        // Promouvoir quelqu'un de la liste d'attente si nécessaire
        $this->promoteFromWaitingList($event);

        $this->addFlash('success', 'Votre inscription a été annulée.');
        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    #[Route('/my-registrations', name: 'my_event_registrations')]
    public function myRegistrations(): Response
    {
        $user = $this->getUser();
        
        $registrations = $this->entityManager->getRepository(EventRegistration::class)
            ->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.user = :user')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['registered', 'waiting_list'])
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('events/my_registrations.html.twig', [
            'registrations' => $registrations
        ]);
    }

    #[Route('/recommendations', name: 'event_recommendations')]
    public function recommendations(): Response
    {
        $user = $this->getUser();
        $recommendedEvents = $this->registrationValidator->getRecommendedEventsForUser($user);

        return $this->render('events/recommendations.html.twig', [
            'recommended_events' => $recommendedEvents,
            'user_level' => $user->getDynamicAttribute('level')
        ]);
    }

    /**
     * Promouvoir le premier utilisateur de la liste d'attente
     */
    private function promoteFromWaitingList(Event $event): void
    {
        if (!$event->requiresWaitingList()) {
            $waitingRegistration = $this->entityManager->getRepository(EventRegistration::class)
                ->createQueryBuilder('r')
                ->where('r.event = :event')
                ->andWhere('r.status = :status')
                ->setParameter('event', $event)
                ->setParameter('status', 'waiting_list')
                ->orderBy('r.registeredAt', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNull();

            if ($waitingRegistration) {
                $waitingRegistration->setStatus('registered');
                $this->entityManager->flush();

                // TODO: Envoyer notification email à l'utilisateur promu
            }
        }
    }
}