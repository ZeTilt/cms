<?php

namespace App\Controller;

use App\Entity\Event;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

        $queryBuilder = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('e.startDate', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

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

        $totalEvents = $totalQuery->getQuery()->getSingleScalarResult();
        $totalPages = ceil($totalEvents / $limit);

        return $this->render('events/index.html.twig', [
            'events' => $events,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEvents' => $totalEvents,
            'currentType' => $type,
            'currentFilter' => $filter,
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

        // Get events for the month
        $startDate = new \DateTimeImmutable("$year-$month-01");
        $endDate = $startDate->modify('last day of this month');

        $events = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.startDate >= :start AND e.startDate <= :end')
            ->setParameter('status', 'published')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('events/calendar.html.twig', [
            'events' => $events,
            'year' => $year,
            'month' => $month,
            'currentDate' => $startDate,
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
}