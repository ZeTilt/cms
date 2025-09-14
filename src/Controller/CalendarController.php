<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CalendarController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository
    ) {}

    #[Route('/calendrier', name: 'public_calendar')]
    public function index(Request $request): Response
    {
        $currentDate = new \DateTime();
        $year = $request->query->getInt('year', $currentDate->format('Y'));
        $month = $request->query->getInt('month', $currentDate->format('n'));

        // Validation des paramètres
        $year = max(2020, min(2030, $year));
        $month = max(1, min(12, $month));

        $events = $this->eventRepository->findEventsByMonth($year, $month);
        $upcomingEvents = $this->eventRepository->findUpcomingEvents(5);

        // Calculer le mois précédent et suivant
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        return $this->render('calendar/index.html.twig', [
            'events' => $events,
            'upcoming_events' => $upcomingEvents,
            'current_year' => $year,
            'current_month' => $month,
            'prev_year' => $prevYear,
            'prev_month' => $prevMonth,
            'next_year' => $nextYear,
            'next_month' => $nextMonth,
            'current_date' => $currentDate,
        ]);
    }

    #[Route('/calendrier/api/events', name: 'calendar_api_events')]
    public function apiEvents(Request $request): JsonResponse
    {
        $year = $request->query->getInt('year', date('Y'));
        $month = $request->query->getInt('month', date('n'));
        
        $events = $this->eventRepository->findEventsByMonth($year, $month);
        
        $eventData = [];
        foreach ($events as $event) {
            $eventData[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'start' => $event->getStartDate()->format('Y-m-d H:i:s'),
                'end' => $event->getEndDate()?->format('Y-m-d H:i:s'),
                'type' => $event->getType(),
                'location' => $event->getLocation(),
                'description' => $event->getDescription(),
                'color' => $event->getColor(),
                'maxParticipants' => $event->getMaxParticipants(),
                'currentParticipants' => $event->getCurrentParticipants(),
                'isFullyBooked' => $event->isFullyBooked(),
                'availableSpots' => $event->getAvailableSpots(),
            ];
        }

        return new JsonResponse($eventData);
    }

    #[Route('/calendrier/evenement/{id}', name: 'calendar_event_detail')]
    public function eventDetail(int $id): Response
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event || $event->getStatus() !== 'active') {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        return $this->render('calendar/event_detail.html.twig', [
            'event' => $event,
        ]);
    }
}