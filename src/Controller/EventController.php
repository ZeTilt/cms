<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventAttribute;
use App\Entity\EventRegistration;
use App\Entity\EventType;
use App\Entity\User;
use App\Service\ModuleManager;
use App\Service\EventRegistrationValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/events')]
#[IsGranted('ROLE_ADMIN')]
class EventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager,
        private SluggerInterface $slugger,
        private TranslatorInterface $translator,
        private EventRegistrationValidator $registrationValidator
    ) {
    }

    #[Route('', name: 'admin_events_list')]
    public function list(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
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
        if ($status && $status !== '') {
            $queryBuilder->andWhere('e.status = :status')
                ->setParameter('status', $status);
        }

        $type = $request->query->get('type');
        if ($type && $type !== '') {
            $queryBuilder->andWhere('e.type = :type')
                ->setParameter('type', $type);
        }

        $events = $queryBuilder->getQuery()->getResult();

        // Count total for pagination (using same filters)
        $countQueryBuilder = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)');
            
        if ($status && $status !== '') {
            $countQueryBuilder->andWhere('e.status = :status')
                ->setParameter('status', $status);
        }
        
        if ($type && $type !== '') {
            $countQueryBuilder->andWhere('e.type = :type')
                ->setParameter('type', $type);
        }
        
        $totalEvents = $countQueryBuilder->getQuery()->getSingleScalarResult();

        $totalPages = ceil($totalEvents / $limit);

        // Get all active event types for the filter
        $eventTypes = $this->entityManager->getRepository(EventType::class)
            ->findBy(['active' => true], ['sortOrder' => 'ASC']);

        return $this->render('admin/events/list.html.twig', [
            'events' => $events,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEvents' => $totalEvents,
            'currentStatus' => $status,
            'currentType' => $type,
            'eventTypes' => $eventTypes,
        ]);
    }

    #[Route('/new', name: 'admin_events_new')]
    public function new(): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
        }

        // Load available pilots (users with pilot=oui attribute)
        $pilots = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->join('u.userAttributes', 'ua')
            ->where('ua.attributeKey = :key')
            ->andWhere('ua.attributeValue = :value')
            ->setParameter('key', 'pilote')
            ->setParameter('value', 'oui')
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();

        // Load available event types
        $eventTypes = $this->entityManager->getRepository(\App\Entity\EventType::class)
            ->findBy(['active' => true], ['sortOrder' => 'ASC']);

        return $this->render('admin/events/edit.html.twig', [
            'event' => new Event(),
            'isEdit' => false,
            'pilots' => $pilots,
            'eventTypes' => $eventTypes,
            'available_operators' => $this->registrationValidator->getAvailableOperators(),
            'available_entities' => $this->registrationValidator->getAvailableEntities(),
            'attributes_details' => $this->registrationValidator->getAttributesWithDetails(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_events_edit', requirements: ['id' => '\d+'])]
    public function edit(Event $event): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
        }

        // Load available pilots (users with pilot=oui attribute)
        $pilots = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->join('u.userAttributes', 'ua')
            ->where('ua.attributeKey = :key')
            ->andWhere('ua.attributeValue = :value')
            ->setParameter('key', 'pilote')
            ->setParameter('value', 'oui')
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();

        // Load available event types
        $eventTypes = $this->entityManager->getRepository(\App\Entity\EventType::class)
            ->findBy(['active' => true], ['sortOrder' => 'ASC']);

        return $this->render('admin/events/edit.html.twig', [
            'event' => $event,
            'isEdit' => true,
            'pilots' => $pilots,
            'eventTypes' => $eventTypes,
            'available_operators' => $this->registrationValidator->getAvailableOperators(),
            'available_entities' => $this->registrationValidator->getAvailableEntities(),
            'attributes_details' => $this->registrationValidator->getAttributesWithDetails(),
        ]);
    }

    #[Route('/save', name: 'admin_events_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
        }

        $eventId = $request->request->get('id');
        $event = $eventId ? $this->entityManager->getRepository(Event::class)->find($eventId) : new Event();

        if (!$event) {
            throw $this->createNotFoundException($this->translator->trans('errors.event_not_found', [], 'events'));
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
            $event->setStartDate(new \DateTimeImmutable($startDate . ' 08:00:00'));
        }

        $endDate = $request->request->get('end_date');
        if ($endDate) {
            $event->setEndDate(new \DateTimeImmutable($endDate . ' 18:00:00'));
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

        // Diving-specific fields
        $clubDepartureTime = $request->request->get('club_departure_time');
        if ($clubDepartureTime && $startDate) {
            $event->setClubDepartureTime(new \DateTimeImmutable($startDate . ' ' . $clubDepartureTime . ':00'));
        }

        $dockDepartureTime = $request->request->get('dock_departure_time');
        if ($dockDepartureTime && $startDate) {
            $event->setDockDepartureTime(new \DateTimeImmutable($startDate . ' ' . $dockDepartureTime . ':00'));
        }

        $pilotId = $request->request->get('pilot');
        if ($pilotId) {
            $pilot = $this->entityManager->getRepository(User::class)->find($pilotId);
            if ($pilot) {
                $event->setPilot($pilot);
            }
        }

        $event->setDivingComments($request->request->get('diving_comments'));

        // Gestion des conditions d'inscription
        $registrationConditions = [];
        $conditions = $request->request->all('registration_conditions') ?? [];
        
        foreach ($conditions as $condition) {
            if (!empty($condition['entity_type']) && !empty($condition['attribute_key']) && !empty($condition['operator'])) {
                $registrationConditions[] = [
                    'entity_type' => $condition['entity_type'],
                    'attribute_key' => $condition['attribute_key'],
                    'operator' => $condition['operator'],
                    'value' => $condition['value'] ?? '',
                    'message' => $condition['message'] ?? "Condition non respectée pour l'attribut " . $condition['attribute_key']
                ];
            }
        }
        
        $event->setRegistrationConditions($registrationConditions);

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

        $this->addFlash('success', $this->translator->trans('success.event_saved', [], 'events'));
        return $this->redirectToRoute('admin_events_edit', ['id' => $event->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_events_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Event $event): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
        }

        $this->entityManager->remove($event);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('success.event_deleted', [], 'events'));
        return $this->redirectToRoute('admin_events_list');
    }

    #[Route('/calendar', name: 'admin_events_calendar')]
    public function calendar(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
        }

        // Get all events to allow JavaScript to filter them for different months
        // This avoids AJAX calls when navigating between months
        $events = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        // Convert events to a simple array for JavaScript consumption
        $eventsData = [];
        foreach ($events as $event) {
            $eventsData[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'shortDescription' => $event->getShortDescription(),
                'startDate' => $event->getStartDate() ? $event->getStartDate()->format('Y-m-d H:i:s') : null,
                'endDate' => $event->getEndDate() ? $event->getEndDate()->format('Y-m-d H:i:s') : null,
                'location' => $event->getLocation(),
                'status' => $event->getStatus(),
                'type' => $event->getType(),
            ];
        }

        // Get all event types for the legend
        $eventTypes = $this->entityManager->getRepository(EventType::class)
            ->findBy(['active' => true], ['sortOrder' => 'ASC']);

        $eventTypesData = [];
        foreach ($eventTypes as $eventType) {
            $eventTypesData[] = [
                'slug' => $eventType->getSlug(),
                'name' => $eventType->getName(),
                'color' => $eventType->getColor(),
            ];
        }

        return $this->render('admin/events/calendar.html.twig', [
            'events' => $eventsData,
            'eventTypes' => $eventTypesData,
        ]);
    }

    #[Route('/{id}/attributes', name: 'admin_events_attributes', requirements: ['id' => '\d+'])]
    public function attributes(Event $event): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
        }

        return $this->render('admin/events/attributes.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/attributes/add', name: 'admin_events_attributes_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addAttribute(Event $event, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
        }

        $key = $request->request->get('attribute_key');
        $type = $request->request->get('attribute_type', 'text');
        $value = $request->request->get('attribute_value', '');

        if (empty($key)) {
            $this->addFlash('error', $this->translator->trans('attributes.key_required', [], 'events'));
            return $this->redirectToRoute('admin_events_attributes', ['id' => $event->getId()]);
        }

        // Check if attribute already exists
        if ($event->getEventAttributeByKey($key)) {
            $this->addFlash('error', $this->translator->trans('attributes.key_already_exists', [], 'events'));
            return $this->redirectToRoute('admin_events_attributes', ['id' => $event->getId()]);
        }

        $event->setAttributeValue($key, $value, $type);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('attributes.added_successfully', [], 'events'));
        return $this->redirectToRoute('admin_events_attributes', ['id' => $event->getId()]);
    }

    #[Route('/{id}/attributes/{attributeId}/delete', name: 'admin_events_attributes_delete', methods: ['POST'], requirements: ['id' => '\d+', 'attributeId' => '\d+'])]
    public function deleteAttribute(Event $event, int $attributeId): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
        }

        $attribute = $this->entityManager->getRepository(EventAttribute::class)->find($attributeId);
        
        if (!$attribute || $attribute->getEvent() !== $event) {
            throw $this->createNotFoundException($this->translator->trans('errors.attribute_not_found', [], 'events'));
        }

        $this->entityManager->remove($attribute);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('attributes.deleted_successfully', [], 'events'));
        return $this->redirectToRoute('admin_events_attributes', ['id' => $event->getId()]);
    }

    #[Route('/{id}/registrations', name: 'admin_events_registrations', requirements: ['id' => '\d+'])]
    public function registrations(Event $event): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
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
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
        }

        $userId = $request->request->getInt('user_id');
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            $this->addFlash('error', $this->translator->trans('errors.user_not_found', [], 'events'));
            return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
        }

        // Check if user is already registered
        if ($event->isUserRegistered($user)) {
            $this->addFlash('error', $this->translator->trans('errors.user_already_registered', [], 'events'));
            return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
        }

        // Check if event accepts registrations
        if (!$event->acceptsRegistrations()) {
            $this->addFlash('error', $this->translator->trans('errors.event_no_registrations', [], 'events'));
            return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
        }

        $registration = new EventRegistration();
        $registration->setEvent($event);
        $registration->setUser($user);
        $registration->setNotes($request->request->get('notes', ''));

        // Determine registration status
        if ($event->requiresWaitingList()) {
            $registration->setStatus('waiting_list');
            $message = $this->translator->trans('success.user_registered_waitlist', [], 'events');
        } else {
            $registration->setStatus('registered');
            $message = $this->translator->trans('success.user_registered', [], 'events');
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
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
        }

        $registration = $this->entityManager->getRepository(EventRegistration::class)->find($registrationId);
        
        if (!$registration || $registration->getEvent() !== $event) {
            throw $this->createNotFoundException($this->translator->trans('errors.registration_not_found', [], 'events'));
        }

        $newStatus = $request->request->get('status');
        $validStatuses = ['registered', 'waiting_list', 'cancelled', 'no_show'];

        if (!in_array($newStatus, $validStatuses)) {
            $this->addFlash('error', $this->translator->trans('errors.invalid_status', [], 'events'));
            return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
        }

        $oldStatus = $registration->getStatus();
        $registration->setStatus($newStatus);
        $registration->setNotes($request->request->get('notes', $registration->getNotes()));

        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('success.registration_status_updated', [
            '%old_status%' => $oldStatus,
            '%new_status%' => $newStatus
        ], 'events'));
        return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
    }

    #[Route('/{id}/registrations/{registrationId}/delete', name: 'admin_events_registration_delete', methods: ['POST'], requirements: ['id' => '\d+', 'registrationId' => '\d+'])]
    public function deleteRegistration(Event $event, int $registrationId): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException($this->translator->trans('errors.module_not_active', [], 'events'));
        }

        $registration = $this->entityManager->getRepository(EventRegistration::class)->find($registrationId);
        
        if (!$registration || $registration->getEvent() !== $event) {
            throw $this->createNotFoundException($this->translator->trans('errors.registration_not_found', [], 'events'));
        }

        $userName = $registration->getUser()->getUsername();
        $this->entityManager->remove($registration);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('success.registration_deleted', [
            '%username%' => $userName
        ], 'events'));
        return $this->redirectToRoute('admin_events_registrations', ['id' => $event->getId()]);
    }

    #[Route('/ajax/attributes/{entityType}', name: 'admin_events_ajax_attributes', methods: ['GET'])]
    public function getEntityAttributes(string $entityType): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Module not active');
        }

        $attributes = $this->registrationValidator->getAvailableAttributes($entityType);
        $detailed = [];
        
        foreach ($attributes as $key => $label) {
            $detailed[$key] = [
                'label' => $label,
                'details' => $this->registrationValidator->getAttributeDetails($entityType, $key)
            ];
        }

        return $this->json($detailed);
    }
}