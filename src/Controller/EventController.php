<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventAttribute;
use App\Entity\EventRegistration;
use App\Entity\User;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/events')]
#[IsGranted('ROLE_ADMIN')]
class EventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager,
        private SluggerInterface $slugger
    ) {
    }

    #[Route('', name: 'admin_events_list')]
    public function list(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->orderBy('e.startDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $status = $request->query->get('status');
        if ($status) {
            $queryBuilder->andWhere('e.status = :status')
                ->setParameter('status', $status);
        }

        $type = $request->query->get('type');
        if ($type) {
            $queryBuilder->andWhere('e.type = :type')
                ->setParameter('type', $type);
        }

        $events = $queryBuilder->getQuery()->getResult();

        // Count total for pagination
        $totalEvents = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = ceil($totalEvents / $limit);

        return $this->render('admin/events/list.html.twig', [
            'events' => $events,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEvents' => $totalEvents,
            'currentStatus' => $status,
            'currentType' => $type,
        ]);
    }

    #[Route('/new', name: 'admin_events_new')]
    public function new(): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        return $this->render('admin/events/edit.html.twig', [
            'event' => new Event(),
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_events_edit', requirements: ['id' => '\d+'])]
    public function edit(Event $event): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        return $this->render('admin/events/edit.html.twig', [
            'event' => $event,
            'isEdit' => true,
        ]);
    }

    #[Route('/save', name: 'admin_events_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $eventId = $request->request->get('id');
        $event = $eventId ? $this->entityManager->getRepository(Event::class)->find($eventId) : new Event();

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        // Set organizer if new event
        if (!$eventId) {
            $event->setOrganizer($this->getUser());
        }

        // Basic fields
        $event->setTitle($request->request->get('title'));
        
        // Set slug (use provided slug or generate from title)
        $slug = $request->request->get('slug');
        if (empty($slug)) {
            $slug = $this->slugger->slug($request->request->get('title'))->lower();
        } else {
            $slug = $this->slugger->slug($slug)->lower();
        }
        $event->setSlug($slug);

        $event->setDescription($request->request->get('description'));
        $event->setShortDescription($request->request->get('short_description'));
        $event->setLocation($request->request->get('location'));
        $event->setAddress($request->request->get('address'));
        $event->setStatus($request->request->get('status', 'draft'));
        $event->setType($request->request->get('type', 'event'));

        // Dates
        $startDate = $request->request->get('start_date');
        if ($startDate) {
            $event->setStartDate(new \DateTimeImmutable($startDate));
        }

        $endDate = $request->request->get('end_date');
        if ($endDate) {
            $event->setEndDate(new \DateTimeImmutable($endDate));
        }

        // Registration settings
        $event->setRequiresRegistration($request->request->getBoolean('requires_registration'));
        $maxParticipants = $request->request->get('max_participants');
        if ($maxParticipants) {
            $event->setMaxParticipants((int) $maxParticipants);
        }

        // Tags
        $tagsString = $request->request->get('tags', '');
        $tags = $tagsString ? array_map('trim', explode(',', $tagsString)) : [];
        $event->setTags($tags);

        // Recurring settings
        $event->setIsRecurring($request->request->getBoolean('is_recurring'));
        if ($event->isRecurring()) {
            $recurringConfig = [
                'frequency' => $request->request->get('recurring_frequency'),
                'interval' => $request->request->getInt('recurring_interval', 1),
                'end_after' => $request->request->get('recurring_end_after'),
                'end_date' => $request->request->get('recurring_end_date'),
            ];
            $event->setRecurringConfig($recurringConfig);
        }

        if (!$eventId) {
            $this->entityManager->persist($event);
        }

        // Handle event attributes
        $attributes = $request->request->all('attributes') ?? [];
        foreach ($attributes as $key => $data) {
            if (!empty($data['value']) || !empty($data['type'])) {
                $value = $data['value'] ?? '';
                $type = $data['type'] ?? 'text';
                $event->setAttributeValue($key, $value, $type);
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Event saved successfully!');
        return $this->redirectToRoute('admin_events_edit', ['id' => $event->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_events_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Event $event): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $this->entityManager->remove($event);
        $this->entityManager->flush();

        $this->addFlash('success', 'Event deleted successfully!');
        return $this->redirectToRoute('admin_events_list');
    }

    #[Route('/calendar', name: 'admin_events_calendar')]
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
            ->where('e.startDate >= :start AND e.startDate <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/events/calendar.html.twig', [
            'events' => $events,
            'year' => $year,
            'month' => $month,
            'currentDate' => $startDate,
        ]);
    }

    #[Route('/{id}/attributes', name: 'admin_events_attributes', requirements: ['id' => '\d+'])]
    public function attributes(Event $event): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        return $this->render('admin/events/attributes.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/attributes/add', name: 'admin_events_attributes_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addAttribute(Event $event, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $key = $request->request->get('attribute_key');
        $type = $request->request->get('attribute_type', 'text');
        $value = $request->request->get('attribute_value', '');

        if (empty($key)) {
            $this->addFlash('error', 'Attribute key is required');
            return $this->redirectToRoute('admin_events_attributes', ['id' => $event->getId()]);
        }

        // Check if attribute already exists
        if ($event->getEventAttributeByKey($key)) {
            $this->addFlash('error', 'An attribute with this key already exists');
            return $this->redirectToRoute('admin_events_attributes', ['id' => $event->getId()]);
        }

        $event->setAttributeValue($key, $value, $type);
        $this->entityManager->flush();

        $this->addFlash('success', 'Attribute added successfully');
        return $this->redirectToRoute('admin_events_attributes', ['id' => $event->getId()]);
    }

    #[Route('/{id}/attributes/{attributeId}/delete', name: 'admin_events_attributes_delete', methods: ['POST'], requirements: ['id' => '\d+', 'attributeId' => '\d+'])]
    public function deleteAttribute(Event $event, int $attributeId): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $attribute = $this->entityManager->getRepository(EventAttribute::class)->find($attributeId);
        
        if (!$attribute || $attribute->getEvent() !== $event) {
            throw $this->createNotFoundException('Attribute not found');
        }

        $this->entityManager->remove($attribute);
        $this->entityManager->flush();

        $this->addFlash('success', 'Attribute deleted successfully');
        return $this->redirectToRoute('admin_events_attributes', ['id' => $event->getId()]);
    }

    #[Route('/{id}/registrations', name: 'admin_events_registrations', requirements: ['id' => '\d+'])]
    public function registrations(Event $event): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        // Get all users who are not already registered
        $allUsers = $this->entityManager->getRepository(User::class)->findAll();
        $availableUsers = array_filter($allUsers, fn($user) => !$event->isUserRegistered($user));

        return $this->render('admin/events/registrations.html.twig', [
            'event' => $event,
            'availableUsers' => $availableUsers,
        ]);
    }

    #[Route('/{id}/register', name: 'admin_events_register', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function register(Event $event, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $userId = $request->request->getInt('user_id');
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            $this->addFlash('error', 'User not found');
            return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
        }

        // Check if user is already registered
        if ($event->isUserRegistered($user)) {
            $this->addFlash('error', 'User is already registered for this event');
            return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
        }

        // Check if event accepts registrations
        if (!$event->acceptsRegistrations()) {
            $this->addFlash('error', 'This event does not accept registrations');
            return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
        }

        $registration = new EventRegistration();
        $registration->setEvent($event);
        $registration->setUser($user);
        $registration->setNotes($request->request->get('notes', ''));

        // Determine registration status
        if ($event->requiresWaitingList()) {
            $registration->setStatus('waiting_list');
            $message = 'User registered on waiting list';
        } else {
            $registration->setStatus('registered');
            $message = 'User registered successfully';
        }

        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        $this->addFlash('success', $message);
        return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
    }

    #[Route('/{id}/registrations/{registrationId}/status', name: 'admin_events_registration_status', methods: ['POST'], requirements: ['id' => '\d+', 'registrationId' => '\d+'])]
    public function updateRegistrationStatus(Event $event, int $registrationId, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $registration = $this->entityManager->getRepository(EventRegistration::class)->find($registrationId);
        
        if (!$registration || $registration->getEvent() !== $event) {
            throw $this->createNotFoundException('Registration not found');
        }

        $newStatus = $request->request->get('status');
        $validStatuses = ['registered', 'waiting_list', 'cancelled', 'no_show'];

        if (!in_array($newStatus, $validStatuses)) {
            $this->addFlash('error', 'Invalid status');
            return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
        }

        $oldStatus = $registration->getStatus();
        $registration->setStatus($newStatus);
        $registration->setNotes($request->request->get('notes', $registration->getNotes()));

        $this->entityManager->flush();

        $this->addFlash('success', "Registration status updated from {$oldStatus} to {$newStatus}");
        return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
    }

    #[Route('/{id}/registrations/{registrationId}/delete', name: 'admin_events_registration_delete', methods: ['POST'], requirements: ['id' => '\d+', 'registrationId' => '\d+'])]
    public function deleteRegistration(Event $event, int $registrationId): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Events module is not active');
        }

        $registration = $this->entityManager->getRepository(EventRegistration::class)->find($registrationId);
        
        if (!$registration || $registration->getEvent() !== $event) {
            throw $this->createNotFoundException('Registration not found');
        }

        $userName = $registration->getUser()->getUsername();
        $this->entityManager->remove($registration);
        $this->entityManager->flush();

        $this->addFlash('success', "Registration for {$userName} deleted successfully");
        return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
    }
}