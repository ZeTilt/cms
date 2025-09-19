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

        // Set meeting point if provided
        $meetingPoint = $request->request->get('meeting_point');
        if ($meetingPoint && in_array($meetingPoint, ['club', 'site'])) {
            $participation->setMeetingPoint($meetingPoint);
        }

        // Check if event is full - if so, add to waiting list
        $isWaitingList = $event->isFullyBooked();
        $participation->setIsWaitingList($isWaitingList);

        $this->entityManager->persist($participation);
        $this->entityManager->flush();

        if ($isWaitingList) {
            $this->addFlash('warning', 'L\'événement est complet. Vous avez été ajouté à la liste d\'attente.');
        } else {
            $this->addFlash('success', 'Votre inscription a été enregistrée avec succès !');
        }

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

        // Check if there are people on the waiting list to promote
        $waitingListParticipations = $event->getWaitingListParticipations();
        if (!$waitingListParticipations->isEmpty()) {
            // Promote the first person from waiting list
            $firstWaiting = $waitingListParticipations->first();
            $firstWaiting->setIsWaitingList(false);

            $this->addFlash('info', 'Une personne de la liste d\'attente a été automatiquement inscrite.');
        }

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