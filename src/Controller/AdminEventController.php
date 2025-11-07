<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\EventTypeRepository;
use App\Repository\DivingLevelRepository;
use App\Repository\UserRepository;
use App\Repository\BoatRepository;
use App\Service\RecurringEventService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/events')]
#[IsGranted('ROLE_ADMIN')]
class AdminEventController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventTypeRepository $eventTypeRepository,
        private DivingLevelRepository $divingLevelRepository,
        private UserRepository $userRepository,
        private BoatRepository $boatRepository,
        private EntityManagerInterface $entityManager,
        private RecurringEventService $recurringEventService
    ) {}

    #[Route('', name: 'admin_events_list')]
    public function index(Request $request): Response
    {
        $showAll = $request->query->getBoolean('show_all', false);
        $events = $this->eventRepository->findBy([], ['startDate' => 'DESC']);

        // Si on ne veut pas tout afficher, filtrer les événements récurrents
        if (!$showAll) {
            $events = $this->filterRecurringEvents($events);
        }

        return $this->render('admin/events/index.html.twig', [
            'events' => $events,
            'showAll' => $showAll,
        ]);
    }

    /**
     * Filtre les événements récurrents pour ne garder que la prochaine occurrence de chaque série
     */
    private function filterRecurringEvents(array $events): array
    {
        $now = new \DateTime();
        $filtered = [];
        $seriesProcessed = []; // Track which series we've already processed

        foreach ($events as $event) {
            // Si c'est un événement parent (récurrent)
            if ($event->isRecurring()) {
                // Chercher la prochaine occurrence de cette série (parent inclus)
                $parentId = $event->getId();

                if (!isset($seriesProcessed[$parentId])) {
                    $nextOccurrence = null;

                    // Vérifier si le parent lui-même est dans le futur
                    if ($event->getStartDate()->getTimestamp() >= $now->getTimestamp()) {
                        $nextOccurrence = $event;
                    }

                    // Chercher parmi les occurrences générées si elles sont plus proches
                    foreach ($event->getGeneratedEvents() as $generated) {
                        $generatedTime = $generated->getStartDate()->getTimestamp();
                        if ($generatedTime >= $now->getTimestamp()) {
                            if ($nextOccurrence === null || $generatedTime < $nextOccurrence->getStartDate()->getTimestamp()) {
                                $nextOccurrence = $generated;
                            }
                        }
                    }

                    // Ajouter la prochaine occurrence trouvée (parent ou générée)
                    if ($nextOccurrence) {
                        $filtered[] = $nextOccurrence;
                        $seriesProcessed[$parentId] = true;
                    }
                }
                continue;
            }

            // Si c'est une occurrence générée, vérifier si on a déjà traité sa série
            if ($event->isGeneratedEvent() && $event->getParentEvent()) {
                $parentId = $event->getParentEvent()->getId();
                // Si la série a déjà été traitée via le parent, on ignore cette occurrence
                if (isset($seriesProcessed[$parentId])) {
                    continue;
                }
            }

            // Si ce n'est pas un événement récurrent (ni parent ni généré), l'ajouter normalement
            if (!$event->isRecurring() && !$event->isGeneratedEvent()) {
                $filtered[] = $event;
            }
        }

        return $filtered;
    }

    #[Route('/new', name: 'admin_events_new')]
    public function new(Request $request): Response
    {
        $event = new Event();
        
        if ($request->isMethod('POST')) {
            // Simple form handling - dans une vraie app, utiliser Symfony Forms
            $event->setTitle($request->request->get('title'));
            $event->setDescription($request->request->get('description'));
            $event->setStartDate(new \DateTime($request->request->get('start_date')));
            
            if ($request->request->get('end_date')) {
                $event->setEndDate(new \DateTime($request->request->get('end_date')));
            }
            
            $event->setLocation($request->request->get('location'));
            
            // Gérer le type d'événement (obligatoire maintenant)
            $eventTypeId = $request->request->get('event_type_id');
            if (!$eventTypeId) {
                $this->addFlash('error', 'Vous devez sélectionner un type d\'événement.');
                return $this->render('admin/events/edit.html.twig', [
                    'event' => $event,
                    'isNew' => true,
                    'eventTypes' => $this->eventTypeRepository->findActive(),
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

            // Gérer le besoin d'un pilote
            $needsPilot = (bool) $request->request->get('needs_pilot');
            $event->setNeedsPilot($needsPilot);

            if ($needsPilot) {
                $pilotId = $request->request->get('pilot_id');
                if ($pilotId) {
                    $pilot = $this->userRepository->find($pilotId);
                    $event->setPilot($pilot);
                }
            } else {
                $event->setPilot(null);
            }

            // Gérer le directeur de plongée
            $divingDirectorId = $request->request->get('diving_director_id');
            if ($divingDirectorId) {
                $divingDirector = $this->userRepository->find($divingDirectorId);
                $event->setDivingDirector($divingDirector);
            } else {
                // Par défaut, l'utilisateur connecté est le DP
                $event->setDivingDirector($this->getUser());
            }

            // Gérer le bateau
            $boatId = $request->request->get('boat_id');
            if ($boatId) {
                $boat = $this->boatRepository->find($boatId);
                $event->setBoat($boat);
            }

            $this->entityManager->persist($event);
            $this->entityManager->flush();
            
            // Gérer la récurrence après la première sauvegarde
            $this->handleRecurrence($event, $request);
            
            $this->addFlash('success', 'Événement créé avec succès !');
            
            return $this->redirectToRoute('admin_events_list');
        }
        
        $eventTypes = $this->eventTypeRepository->findActive();
        $divingLevels = $this->divingLevelRepository->findAllOrdered();
        $pilots = $this->userRepository->findPilots();
        $divingDirectors = $this->userRepository->findDivingDirectors();
        $boats = $this->boatRepository->findActive();

        return $this->render('admin/events/edit.html.twig', [
            'event' => $event,
            'isNew' => true,
            'eventTypes' => $eventTypes,
            'divingLevels' => $divingLevels,
            'pilots' => $pilots,
            'divingDirectors' => $divingDirectors,
            'boats' => $boats,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_events_edit')]
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
            
            // Gérer le type d'événement (obligatoire maintenant)
            $eventTypeId = $request->request->get('event_type_id');
            if (!$eventTypeId) {
                $this->addFlash('error', 'Vous devez sélectionner un type d\'événement.');
                return $this->render('admin/events/edit.html.twig', [
                    'event' => $event,
                    'isNew' => false,
                    'eventTypes' => $this->eventTypeRepository->findActive(),
                ]);
            }
            
            $eventType = $this->eventTypeRepository->find($eventTypeId);
            $event->setEventType($eventType);
            $event->setMaxParticipants($request->request->get('max_participants') ? (int)$request->request->get('max_participants') : null);
            $event->setStatus($request->request->get('status', 'active'));

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

            // Gérer le besoin d'un pilote
            $needsPilot = (bool) $request->request->get('needs_pilot');
            $event->setNeedsPilot($needsPilot);

            if ($needsPilot) {
                $pilotId = $request->request->get('pilot_id');
                if ($pilotId) {
                    $pilot = $this->userRepository->find($pilotId);
                    $event->setPilot($pilot);
                } else {
                    $event->setPilot(null);
                }
            } else {
                $event->setPilot(null);
            }

            // Gérer le directeur de plongée
            $divingDirectorId = $request->request->get('diving_director_id');
            if ($divingDirectorId) {
                $divingDirector = $this->userRepository->find($divingDirectorId);
                $event->setDivingDirector($divingDirector);
            } else {
                $event->setDivingDirector(null);
            }

            // Gérer le bateau
            $boatId = $request->request->get('boat_id');
            if ($boatId) {
                $boat = $this->boatRepository->find($boatId);
                $event->setBoat($boat);
            } else {
                $event->setBoat(null);
            }

            $this->entityManager->flush();
            
            // Gérer la récurrence après la sauvegarde
            $this->handleRecurrence($event, $request);
            
            $this->addFlash('success', 'Événement mis à jour avec succès !');
            
            return $this->redirectToRoute('admin_events_list');
        }
        
        $eventTypes = $this->eventTypeRepository->findActive();
        $divingLevels = $this->divingLevelRepository->findAllOrdered();
        $pilots = $this->userRepository->findPilots();
        $divingDirectors = $this->userRepository->findDivingDirectors();
        $boats = $this->boatRepository->findActive();

        return $this->render('admin/events/edit.html.twig', [
            'event' => $event,
            'isNew' => false,
            'eventTypes' => $eventTypes,
            'divingLevels' => $divingLevels,
            'pilots' => $pilots,
            'divingDirectors' => $divingDirectors,
            'boats' => $boats,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_events_delete')]
    public function delete(Event $event): Response
    {
        $this->entityManager->remove($event);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Événement supprimé avec succès !');
        
        return $this->redirectToRoute('admin_events_list');
    }

    #[Route('/{id}/delete-series', name: 'admin_events_delete_series')]
    public function deleteSeries(Event $event): Response
    {
        if (!$event->isRecurring()) {
            $this->addFlash('error', 'Cet événement n\'est pas récurrent.');
            return $this->redirectToRoute('admin_events_list');
        }

        $count = $event->getGeneratedEvents()->count() + 1; // +1 pour l'événement parent

        // Supprimer tous les événements générés
        $this->recurringEventService->removeGeneratedEvents($event);
        
        // Supprimer l'événement parent
        $this->entityManager->remove($event);
        $this->entityManager->flush();
        
        $this->addFlash('success', "Série récurrente supprimée avec succès ({$count} événements).");
        
        return $this->redirectToRoute('admin_events_list');
    }

    #[Route('/{id}/delete-from', name: 'admin_events_delete_from')]
    public function deleteFromOccurrence(Event $event): Response
    {
        if (!$event->isGeneratedEvent()) {
            $this->addFlash('error', 'Cette fonctionnalité est uniquement disponible pour les occurrences d\'événements récurrents.');
            return $this->redirectToRoute('admin_events_list');
        }

        $count = $this->recurringEventService->deleteFromOccurrence($event);
        
        $this->addFlash('success', "Suppression effectuée : {$count} événement(s) supprimé(s) à partir de cette occurrence.");
        
        return $this->redirectToRoute('admin_events_list');
    }

    /**
     * Gère les paramètres de récurrence d'un événement
     */
    private function handleRecurrence(Event $event, Request $request): void
    {
        $isRecurring = $request->request->getBoolean('is_recurring', false);
        $event->setRecurring($isRecurring);

        if ($isRecurring) {
            // Configurer les paramètres de récurrence
            $event->setRecurrenceType($request->request->get('recurrence_type', 'weekly'));
            $event->setRecurrenceInterval($request->request->getInt('recurrence_interval', 1));
            
            // Gérer les jours de la semaine pour la récurrence hebdomadaire
            $weekdays = $request->request->all('recurrence_weekdays');
            if (!empty($weekdays)) {
                $event->setRecurrenceWeekdays(array_map('intval', $weekdays));
            } else {
                $event->setRecurrenceWeekdays(null);
            }
            
            // Gérer la date de fin de récurrence
            $endDateString = $request->request->get('recurrence_end_date');
            if ($endDateString) {
                $endDate = new \DateTime($endDateString);
                $event->setRecurrenceEndDate($endDate);
            } else {
                $event->setRecurrenceEndDate(null);
            }
            
            // Générer les événements récurrents
            $generatedEvents = $this->recurringEventService->generateRecurringEvents($event);
            
            $count = count($generatedEvents);
            $this->addFlash('info', "{$count} événement(s) récurrent(s) généré(s) automatiquement.");
            
        } else {
            // Si plus récurrent, supprimer les événements générés existants
            if ($event->getId()) {
                $this->recurringEventService->removeGeneratedEvents($event);
            }
            
            // Réinitialiser les paramètres de récurrence
            $event->setRecurrenceType(null);
            $event->setRecurrenceInterval(null);
            $event->setRecurrenceWeekdays(null);
            $event->setRecurrenceEndDate(null);
        }
    }
}