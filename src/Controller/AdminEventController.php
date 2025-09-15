<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\EventTypeRepository;
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
        private EntityManagerInterface $entityManager,
        private RecurringEventService $recurringEventService
    ) {}

    #[Route('', name: 'admin_events_list')]
    public function index(): Response
    {
        $events = $this->eventRepository->findBy([], ['startDate' => 'DESC']);
        
        return $this->render('admin/events/index.html.twig', [
            'events' => $events,
        ]);
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
            
            // Gérer les conditions d'inscription
            $event->setMinDivingLevel($request->request->get('min_diving_level') ?: null);
            $event->setMinAge($request->request->get('min_age') ? (int)$request->request->get('min_age') : null);
            $event->setMaxAge($request->request->get('max_age') ? (int)$request->request->get('max_age') : null);
            $event->setRequiresMedicalCertificate($request->request->getBoolean('requires_medical_certificate', false));
            $event->setMedicalCertificateValidityDays($request->request->get('medical_certificate_validity_days') ? (int)$request->request->get('medical_certificate_validity_days') : null);
            $event->setRequiresSwimmingTest($request->request->getBoolean('requires_swimming_test', false));
            $event->setAdditionalRequirements($request->request->get('additional_requirements') ?: null);
            
            $this->entityManager->persist($event);
            $this->entityManager->flush();
            
            // Gérer la récurrence après la première sauvegarde
            $this->handleRecurrence($event, $request);
            
            $this->addFlash('success', 'Événement créé avec succès !');
            
            return $this->redirectToRoute('admin_events_list');
        }
        
        $eventTypes = $this->eventTypeRepository->findActive();
        
        return $this->render('admin/events/edit.html.twig', [
            'event' => $event,
            'isNew' => true,
            'eventTypes' => $eventTypes,
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
            
            // Gérer les conditions d'inscription
            $event->setMinDivingLevel($request->request->get('min_diving_level') ?: null);
            $event->setMinAge($request->request->get('min_age') ? (int)$request->request->get('min_age') : null);
            $event->setMaxAge($request->request->get('max_age') ? (int)$request->request->get('max_age') : null);
            $event->setRequiresMedicalCertificate($request->request->getBoolean('requires_medical_certificate', false));
            $event->setMedicalCertificateValidityDays($request->request->get('medical_certificate_validity_days') ? (int)$request->request->get('medical_certificate_validity_days') : null);
            $event->setRequiresSwimmingTest($request->request->getBoolean('requires_swimming_test', false));
            $event->setAdditionalRequirements($request->request->get('additional_requirements') ?: null);
            
            $this->entityManager->flush();
            
            // Gérer la récurrence après la sauvegarde
            $this->handleRecurrence($event, $request);
            
            $this->addFlash('success', 'Événement mis à jour avec succès !');
            
            return $this->redirectToRoute('admin_events_list');
        }
        
        $eventTypes = $this->eventTypeRepository->findActive();
        
        return $this->render('admin/events/edit.html.twig', [
            'event' => $event,
            'isNew' => false,
            'eventTypes' => $eventTypes,
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