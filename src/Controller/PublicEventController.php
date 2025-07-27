<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/events')]
class PublicEventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager
    ) {
    }

    #[Route('', name: 'events_index')]
    public function index(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        try {
            $queryBuilder = $this->entityManager->getRepository(Event::class)
                ->createQueryBuilder('e')
                ->where('e.status = :status')
                ->setParameter('status', 'published')
                ->orderBy('e.startDate', 'ASC')
                ->setFirstResult($offset)
                ->setMaxResults($limit);
        } catch (\Exception $e) {
            throw new \Exception('Error in query: ' . $e->getMessage());
        }

        // Filter by type if provided
        $type = $request->query->get('type');
        if ($type) {
            $queryBuilder->andWhere('e.type = :type')
                ->setParameter('type', $type);
        }

        // Filter by upcoming/past
        $filter = $request->query->get('filter');
        $now = new \DateTimeImmutable();
        
        if ($filter === 'upcoming') {
            $queryBuilder->andWhere('e.startDate > :now')
                ->setParameter('now', $now);
        } elseif ($filter === 'past') {
            $queryBuilder->andWhere('e.endDate < :now')
                ->setParameter('now', $now);
        }

        // Filter by specific date
        $dateFilter = $request->query->get('date');
        if ($dateFilter) {
            try {
                $filterDate = new \DateTimeImmutable($dateFilter);
                $startOfDay = $filterDate->setTime(0, 0, 0);
                $endOfDay = $filterDate->setTime(23, 59, 59);
                
                $queryBuilder->andWhere('e.startDate >= :startOfDay AND e.startDate <= :endOfDay')
                    ->setParameter('startOfDay', $startOfDay)
                    ->setParameter('endOfDay', $endOfDay);
            } catch (\Exception $e) {
                // Invalid date format, ignore filter
            }
        }

        $events = $queryBuilder->getQuery()->getResult();

        // Count total for pagination
        $totalQuery = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->setParameter('status', 'published');

        if ($type) {
            $totalQuery->andWhere('e.type = :type')
                ->setParameter('type', $type);
        }

        if ($filter === 'upcoming') {
            $totalQuery->andWhere('e.startDate > :now')
                ->setParameter('now', $now);
        } elseif ($filter === 'past') {
            $totalQuery->andWhere('e.endDate < :now')
                ->setParameter('now', $now);
        }

        if ($dateFilter) {
            try {
                $filterDate = new \DateTimeImmutable($dateFilter);
                $startOfDay = $filterDate->setTime(0, 0, 0);
                $endOfDay = $filterDate->setTime(23, 59, 59);
                
                $totalQuery->andWhere('e.startDate >= :startOfDay AND e.startDate <= :endOfDay')
                    ->setParameter('startOfDay', $startOfDay)
                    ->setParameter('endOfDay', $endOfDay);
            } catch (\Exception $e) {
                // Invalid date format, ignore filter
            }
        }

        $totalEvents = $totalQuery->getQuery()->getSingleScalarResult();
        $totalPages = ceil($totalEvents / $limit);

        return $this->render('events/index.html.twig', [
            'events' => $events,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEvents' => $totalEvents,
            'currentType' => $type,
            'currentFilter' => $filter,
            'currentDate' => $dateFilter,
        ]);
    }

    #[Route('/{slug}', name: 'events_show')]
    public function show(string $slug): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $event = $this->entityManager->getRepository(Event::class)
            ->findOneBy(['slug' => $slug, 'status' => 'published']);

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        // Get related events (same type, upcoming)
        $relatedEvents = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.type = :type')
            ->andWhere('e.id != :currentId')
            ->andWhere('e.startDate > :now')
            ->setParameter('status', 'published')
            ->setParameter('type', $event->getType())
            ->setParameter('currentId', $event->getId())
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        return $this->render('events/show.html.twig', [
            'event' => $event,
            'relatedEvents' => $relatedEvents,
        ]);
    }

    #[Route('/calendar/view', name: 'events_calendar')]
    public function calendar(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $year = $request->query->getInt('year', (int) date('Y'));
        $month = $request->query->getInt('month', (int) date('m'));

        // Get events for the month and adjacent months for navigation hints
        $startDate = new \DateTimeImmutable("$year-$month-01");
        $endDate = $startDate->modify('last day of this month');

        // Get events for current month with some padding
        $paddedStart = $startDate->modify('-5 days');
        $paddedEnd = $endDate->modify('+5 days');
        
        $events = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.startDate >= :start AND e.startDate <= :end')
            ->setParameter('status', 'published')
            ->setParameter('start', $paddedStart)
            ->setParameter('end', $paddedEnd)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        // Get counts for previous and next month for navigation hints
        $prevMonthStart = new \DateTimeImmutable("$year-$month-01");
        $prevMonthStart = $prevMonthStart->modify('-1 month');
        $prevMonthEnd = $prevMonthStart->modify('last day of this month');
        
        $nextMonthStart = new \DateTimeImmutable("$year-$month-01");
        $nextMonthStart = $nextMonthStart->modify('+1 month');
        $nextMonthEnd = $nextMonthStart->modify('last day of this month');
        
        $prevMonthCount = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->andWhere('e.startDate >= :start AND e.startDate <= :end')
            ->setParameter('status', 'published')
            ->setParameter('start', $prevMonthStart)
            ->setParameter('end', $prevMonthEnd)
            ->getQuery()
            ->getSingleScalarResult();

        $nextMonthCount = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->andWhere('e.startDate >= :start AND e.startDate <= :end')
            ->setParameter('status', 'published')
            ->setParameter('start', $nextMonthStart)
            ->setParameter('end', $nextMonthEnd)
            ->getQuery()
            ->getSingleScalarResult();

        // Convert events to JSON-serializable format
        $eventsData = [];
        foreach ($events as $event) {
            $eventsData[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'slug' => $event->getSlug(),
                'type' => $event->getType(),
                'startDate' => $event->getStartDate() ? $event->getStartDate()->format('Y-m-d\TH:i:s') : null,
                'endDate' => $event->getEndDate() ? $event->getEndDate()->format('Y-m-d\TH:i:s') : null,
                'location' => $event->getLocation(),
                'shortDescription' => $event->getShortDescription(),
                'maxParticipants' => $event->getMaxParticipants(),
            ];
        }
        
        

        return $this->render('events/calendar.html.twig', [
            'events' => $eventsData,
            'year' => $year,
            'month' => $month,
            'currentDate' => $startDate,
            'prevMonthCount' => $prevMonthCount,
            'nextMonthCount' => $nextMonthCount,
        ]);
    }

    #[Route('/api/calendar-data', name: 'events_api_calendar')]
    public function calendarApi(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            return $this->json(['error' => 'Events module is not active'], 404);
        }

        $start = $request->query->get('start');
        $end = $request->query->get('end');

        if (!$start || !$end) {
            return $this->json(['error' => 'Start and end dates are required'], 400);
        }

        $startDate = new \DateTimeImmutable($start);
        $endDate = new \DateTimeImmutable($end);

        $events = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.startDate >= :start AND e.startDate <= :end')
            ->setParameter('status', 'published')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        $calendarEvents = [];
        foreach ($events as $event) {
            $calendarEvents[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'start' => $event->getStartDate()->format('Y-m-d\TH:i:s'),
                'end' => $event->getEndDate() ? $event->getEndDate()->format('Y-m-d\TH:i:s') : null,
                'url' => $this->generateUrl('events_show', ['slug' => $event->getSlug()]),
                'description' => $event->getShortDescription(),
                'location' => $event->getLocation(),
                'backgroundColor' => $this->getEventColor($event->getType()),
            ];
        }

        return $this->json($calendarEvents);
    }

    private function getEventColor(string $type): string
    {
        return match ($type) {
            'meeting' => '#3b82f6',      // Blue
            'conference' => '#8b5cf6',   // Purple
            'workshop' => '#f59e0b',     // Amber
            'event' => '#10b981',        // Emerald
            default => '#6b7280',        // Gray
        };
    }

    #[Route('/{slug}/register', name: 'event_register', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function register(string $slug, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $event = $this->entityManager->getRepository(Event::class)
            ->findOneBy(['slug' => $slug, 'status' => 'published']);

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        if (!$event->isRequiresRegistration() || !$event->acceptsRegistrations()) {
            $this->addFlash('error', 'Registration is not available for this event.');
            return $this->redirectToRoute('events_show', ['slug' => $slug]);
        }

        $user = $this->getUser();
        
        // Check if user is already registered
        $existingRegistration = $this->entityManager->getRepository(EventRegistration::class)
            ->findOneBy(['event' => $event, 'user' => $user]);

        if ($existingRegistration) {
            $this->addFlash('warning', 'You are already registered for this event.');
            return $this->redirectToRoute('events_show', ['slug' => $slug]);
        }

        // Verify CSRF token
        if (!$this->isCsrfTokenValid('register_' . $event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('events_show', ['slug' => $slug]);
        }

        $registration = new EventRegistration();
        $registration->setEvent($event);
        $registration->setUser($user);
        $registration->setRegisteredAt(new \DateTimeImmutable());

        // Determine registration status
        if ($event->requiresWaitingList()) {
            $registration->setStatus('waiting_list');
            $message = 'You have been added to the waiting list for this event.';
        } else {
            $registration->setStatus('registered');
            $message = 'You have been successfully registered for this event.';
        }

        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        $this->addFlash('success', $message);
        return $this->redirectToRoute('events_show', ['slug' => $slug]);
    }

    #[Route('/{slug}/unregister', name: 'event_unregister', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unregister(string $slug, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $event = $this->entityManager->getRepository(Event::class)
            ->findOneBy(['slug' => $slug, 'status' => 'published']);

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $user = $this->getUser();
        
        $registration = $this->entityManager->getRepository(EventRegistration::class)
            ->findOneBy(['event' => $event, 'user' => $user]);

        if (!$registration) {
            $this->addFlash('warning', 'You are not registered for this event.');
            return $this->redirectToRoute('events_show', ['slug' => $slug]);
        }

        // Verify CSRF token
        if (!$this->isCsrfTokenValid('unregister_' . $event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('events_show', ['slug' => $slug]);
        }

        $this->entityManager->remove($registration);
        $this->entityManager->flush();

        // If someone was on the waiting list, promote them
        $waitingListRegistration = $this->entityManager->getRepository(EventRegistration::class)
            ->findOneBy(['event' => $event, 'status' => 'waiting_list'], ['registeredAt' => 'ASC']);

        if ($waitingListRegistration && !$event->requiresWaitingList()) {
            $waitingListRegistration->setStatus('registered');
            $this->entityManager->flush();
            
            // In a real application, you would send an email notification here
        }

        $this->addFlash('success', 'You have been successfully unregistered from this event.');
        return $this->redirectToRoute('events_show', ['slug' => $slug]);
    }
}