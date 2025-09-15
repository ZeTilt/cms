<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Repository\EventParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/events')]
#[IsGranted('ROLE_USER')]
class EventRegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventParticipationRepository $participationRepository
    ) {}

    #[Route('/{id}/register', name: 'event_register', methods: ['POST'])]
    public function register(Event $event): Response
    {
        $user = $this->getUser();

        // Check if user is already registered
        $existingParticipation = $this->participationRepository->findByEventAndUser($event, $user);
        if ($existingParticipation) {
            if ($existingParticipation->isActive()) {
                $this->addFlash('error', 'Vous êtes déjà inscrit à cet événement.');
            } else {
                $this->addFlash('error', 'Votre inscription à cet événement a été annulée.');
            }
            return $this->redirectToRoute('calendar_event_detail', ['id' => $event->getId()]);
        }

        // Check if event is full
        if ($event->isFullyBooked()) {
            $this->addFlash('error', 'Cet événement est complet.');
            return $this->redirectToRoute('calendar_event_detail', ['id' => $event->getId()]);
        }

        // Check user eligibility
        $eligibilityIssues = $event->checkUserEligibility($user);
        if (!empty($eligibilityIssues)) {
            $this->addFlash('error', 'Vous ne remplissez pas les conditions requises pour cet événement :');
            foreach ($eligibilityIssues as $issue) {
                $this->addFlash('error', '• ' . $issue);
            }
            return $this->redirectToRoute('calendar_event_detail', ['id' => $event->getId()]);
        }

        // Create participation
        $participation = new EventParticipation();
        $participation->setEvent($event);
        $participation->setParticipant($user);

        $this->entityManager->persist($participation);

        // Update event participant count
        $event->setCurrentParticipants($event->getCurrentParticipants() + 1);

        $this->entityManager->flush();

        $this->addFlash('success', 'Votre inscription a été enregistrée avec succès !');

        return $this->redirectToRoute('calendar_event_detail', ['id' => $event->getId()]);
    }

    #[Route('/{id}/unregister', name: 'event_unregister', methods: ['POST'])]
    public function unregister(Event $event): Response
    {
        $user = $this->getUser();

        // Find user's participation
        $participation = $this->participationRepository->findByEventAndUser($event, $user);
        if (!$participation || !$participation->isActive()) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à cet événement.');
            return $this->redirectToRoute('calendar_event_detail', ['id' => $event->getId()]);
        }

        // Cancel participation
        $participation->setStatus('cancelled');

        // Update event participant count
        $event->setCurrentParticipants(max(0, $event->getCurrentParticipants() - 1));

        $this->entityManager->flush();

        $this->addFlash('success', 'Votre inscription a été annulée.');

        return $this->redirectToRoute('calendar_event_detail', ['id' => $event->getId()]);
    }

    #[Route('/my-registrations', name: 'my_event_registrations')]
    public function myRegistrations(): Response
    {
        $user = $this->getUser();
        $participations = $this->participationRepository->findByUser($user);

        return $this->render('events/my_registrations.html.twig', [
            'participations' => $participations,
        ]);
    }
}