<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\EventTypeRepository;
use App\Repository\DivingLevelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dp/events')]
#[IsGranted('ROLE_DP')]
class DpEventController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventTypeRepository $eventTypeRepository,
        private DivingLevelRepository $divingLevelRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'dp_events_list')]
    public function index(): Response
    {
        $events = $this->eventRepository->findBy([], ['startDate' => 'DESC']);

        return $this->render('dp/events/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'dp_events_new')]
    public function new(Request $request): Response
    {
        $event = new Event();

        if ($request->isMethod('POST')) {
            $event->setTitle($request->request->get('title'));
            $event->setDescription($request->request->get('description'));
            $event->setStartDate(new \DateTime($request->request->get('start_date')));

            if ($request->request->get('end_date')) {
                $event->setEndDate(new \DateTime($request->request->get('end_date')));
            }

            $event->setLocation($request->request->get('location'));

            // Gérer le type d'événement
            $eventTypeId = $request->request->get('event_type_id');
            if (!$eventTypeId) {
                $this->addFlash('error', 'Vous devez sélectionner un type d\'événement.');
                return $this->render('dp/events/edit.html.twig', [
                    'event' => $event,
                    'isNew' => true,
                    'eventTypes' => $this->eventTypeRepository->findActive(),
                    'divingLevels' => $this->divingLevelRepository->findAll(),
                ]);
            }

            $eventType = $this->eventTypeRepository->find($eventTypeId);
            $event->setEventType($eventType);
            $event->setMaxParticipants($request->request->get('max_participants') ? (int)$request->request->get('max_participants') : null);

            // Gérer le niveau minimum requis
            $minDivingLevelId = $request->request->get('min_diving_level_id');
            if ($minDivingLevelId) {
                $minDivingLevel = $this->divingLevelRepository->find($minDivingLevelId);
                $event->setMinDivingLevel($minDivingLevel);
            }

            // Gérer les heures de RDV
            $clubMeetingTime = $request->request->get('club_meeting_time');
            if ($clubMeetingTime) {
                $event->setClubMeetingTime(new \DateTime($clubMeetingTime));
            }

            $siteMeetingTime = $request->request->get('site_meeting_time');
            if ($siteMeetingTime) {
                $event->setSiteMeetingTime(new \DateTime($siteMeetingTime));
            }

            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès !');

            return $this->redirectToRoute('dp_events_list');
        }

        $eventTypes = $this->eventTypeRepository->findActive();
        $divingLevels = $this->divingLevelRepository->findAll();

        return $this->render('dp/events/edit.html.twig', [
            'event' => $event,
            'isNew' => true,
            'eventTypes' => $eventTypes,
            'divingLevels' => $divingLevels,
        ]);
    }

    #[Route('/{id}/edit', name: 'dp_events_edit')]
    public function edit(Event $event, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $event->setTitle($request->request->get('title'));
            $event->setDescription($request->request->get('description'));
            $event->setStartDate(new \DateTime($request->request->get('start_date')));

            if ($request->request->get('end_date')) {
                $event->setEndDate(new \DateTime($request->request->get('end_date')));
            }

            $event->setLocation($request->request->get('location'));

            // Gérer le type d'événement
            $eventTypeId = $request->request->get('event_type_id');
            if (!$eventTypeId) {
                $this->addFlash('error', 'Vous devez sélectionner un type d\'événement.');
                return $this->render('dp/events/edit.html.twig', [
                    'event' => $event,
                    'isNew' => false,
                    'eventTypes' => $this->eventTypeRepository->findActive(),
                    'divingLevels' => $this->divingLevelRepository->findAll(),
                ]);
            }

            $eventType = $this->eventTypeRepository->find($eventTypeId);
            $event->setEventType($eventType);
            $event->setMaxParticipants($request->request->get('max_participants') ? (int)$request->request->get('max_participants') : null);

            // Gérer le niveau minimum requis
            $minDivingLevelId = $request->request->get('min_diving_level_id');
            if ($minDivingLevelId) {
                $minDivingLevel = $this->divingLevelRepository->find($minDivingLevelId);
                $event->setMinDivingLevel($minDivingLevel);
            } else {
                $event->setMinDivingLevel(null);
            }

            // Gérer les heures de RDV
            $clubMeetingTime = $request->request->get('club_meeting_time');
            if ($clubMeetingTime) {
                $event->setClubMeetingTime(new \DateTime($clubMeetingTime));
            } else {
                $event->setClubMeetingTime(null);
            }

            $siteMeetingTime = $request->request->get('site_meeting_time');
            if ($siteMeetingTime) {
                $event->setSiteMeetingTime(new \DateTime($siteMeetingTime));
            } else {
                $event->setSiteMeetingTime(null);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Événement mis à jour avec succès !');

            return $this->redirectToRoute('dp_events_list');
        }

        $eventTypes = $this->eventTypeRepository->findActive();
        $divingLevels = $this->divingLevelRepository->findAll();

        return $this->render('dp/events/edit.html.twig', [
            'event' => $event,
            'isNew' => false,
            'eventTypes' => $eventTypes,
            'divingLevels' => $divingLevels,
        ]);
    }

    #[Route('/{id}/participants', name: 'dp_events_participants')]
    public function participants(Event $event): Response
    {
        $activeParticipants = $event->getActiveParticipants();
        $waitingList = $event->getWaitingListParticipations();
        $clubMeetingParticipants = $event->getClubMeetingParticipants();
        $siteMeetingParticipants = $event->getSiteMeetingParticipants();

        return $this->render('dp/events/participants.html.twig', [
            'event' => $event,
            'activeParticipants' => $activeParticipants,
            'waitingList' => $waitingList,
            'clubMeetingParticipants' => $clubMeetingParticipants,
            'siteMeetingParticipants' => $siteMeetingParticipants,
        ]);
    }
}