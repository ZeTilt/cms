<?php

namespace App\Service;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;

class RecurringEventService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Génère tous les événements récurrents pour un événement parent
     */
    public function generateRecurringEvents(Event $parentEvent): array
    {
        if (!$parentEvent->isRecurring()) {
            return [];
        }

        // Supprimer les événements générés existants
        $this->removeGeneratedEvents($parentEvent);

        $generatedEvents = [];
        $currentDate = clone $parentEvent->getStartDate();
        $endDate = $parentEvent->getRecurrenceEndDate();
        
        if (!$endDate) {
            // Par défaut, générer sur 1 an maximum
            $endDate = (clone $currentDate)->modify('+1 year');
        }

        // Calculer la durée de l'événement
        $duration = null;
        if ($parentEvent->getEndDate()) {
            $duration = $parentEvent->getStartDate()->diff($parentEvent->getEndDate());
        }

        // Avancer à la première occurrence suivante
        $currentDate = $this->getNextOccurrence($currentDate, $parentEvent);

        while ($currentDate <= $endDate) {
            // Créer une nouvelle instance d'événement
            $newEvent = $this->createEventInstance($parentEvent, $currentDate, $duration);
            
            // Ajouter à la collection parent (important pour le cascade persist)
            $parentEvent->addGeneratedEvent($newEvent);
            
            $generatedEvents[] = $newEvent;
            
            // Passer à la prochaine occurrence
            $currentDate = $this->getNextOccurrence($currentDate, $parentEvent);
        }

        $this->entityManager->flush();
        
        return $generatedEvents;
    }

    /**
     * Supprime tous les événements générés pour un événement parent
     */
    public function removeGeneratedEvents(Event $parentEvent): void
    {
        foreach ($parentEvent->getGeneratedEvents() as $generatedEvent) {
            $this->entityManager->remove($generatedEvent);
        }
        $parentEvent->getGeneratedEvents()->clear();
    }

    /**
     * Calcule la prochaine occurrence selon les règles de récurrence
     */
    private function getNextOccurrence(\DateTimeInterface $currentDate, Event $parentEvent): \DateTimeInterface
    {
        $nextDate = clone $currentDate;
        $recurrenceType = $parentEvent->getRecurrenceType();
        $interval = $parentEvent->getRecurrenceInterval() ?: 1;

        switch ($recurrenceType) {
            case 'daily':
                $nextDate->modify("+{$interval} day");
                break;
                
            case 'weekly':
                $nextDate = $this->getNextWeeklyOccurrence($nextDate, $parentEvent, $interval);
                break;
                
            case 'monthly':
                $nextDate->modify("+{$interval} month");
                break;
                
            default:
                // Si pas de type défini, par défaut hebdomadaire
                $nextDate->modify('+1 week');
        }

        return $nextDate;
    }

    /**
     * Calcule la prochaine occurrence hebdomadaire selon les jours sélectionnés
     */
    private function getNextWeeklyOccurrence(\DateTimeInterface $currentDate, Event $parentEvent, int $interval): \DateTimeInterface
    {
        $weekdays = $parentEvent->getRecurrenceWeekdays();
        
        if (empty($weekdays)) {
            // Si aucun jour spécifié, répéter le même jour de la semaine
            return (clone $currentDate)->modify("+{$interval} week");
        }

        // Trier les jours de la semaine
        sort($weekdays);
        
        $nextDate = clone $currentDate;
        $currentWeekday = (int) $nextDate->format('N'); // 1 = Lundi, 7 = Dimanche
        
        // Chercher le prochain jour dans la même semaine
        $found = false;
        foreach ($weekdays as $weekday) {
            if ($weekday > $currentWeekday) {
                $daysToAdd = $weekday - $currentWeekday;
                $nextDate->modify("+{$daysToAdd} day");
                $found = true;
                break;
            }
        }
        
        // Si aucun jour trouvé cette semaine, passer à la semaine suivante
        if (!$found) {
            $firstWeekday = $weekdays[0];
            $daysToNextWeek = (7 - $currentWeekday) + $firstWeekday;
            $nextDate->modify("+{$daysToNextWeek} day");
            
            // Si intervalle > 1, ajouter les semaines supplémentaires
            if ($interval > 1) {
                $nextDate->modify('+' . (($interval - 1) * 7) . ' day');
            }
        }
        
        return $nextDate;
    }

    /**
     * Crée une nouvelle instance d'événement basée sur l'événement parent
     */
    private function createEventInstance(Event $parentEvent, \DateTimeInterface $startDate, ?\DateInterval $duration): Event
    {
        $newEvent = new Event();
        
        // Copier les propriétés de base
        $newEvent->setTitle($parentEvent->getTitle());
        $newEvent->setDescription($parentEvent->getDescription());
        $newEvent->setLocation($parentEvent->getLocation());
        $newEvent->setEventType($parentEvent->getEventType());
        $newEvent->setMaxParticipants($parentEvent->getMaxParticipants());
        $newEvent->setStatus($parentEvent->getStatus());
        
        // Définir les dates
        $newEvent->setStartDate($startDate);
        if ($duration) {
            $endDate = clone $startDate;
            $endDate->add($duration);
            $newEvent->setEndDate($endDate);
        }
        
        // Marquer comme événement généré
        $newEvent->setParentEvent($parentEvent);
        $newEvent->setRecurring(false); // Les événements générés ne sont pas récurrents
        
        return $newEvent;
    }

    /**
     * Met à jour tous les événements générés quand l'événement parent est modifié
     */
    public function updateGeneratedEvents(Event $parentEvent): void
    {
        if (!$parentEvent->isRecurring()) {
            // Si plus récurrent, supprimer les événements générés
            $this->removeGeneratedEvents($parentEvent);
            return;
        }

        // Régénérer tous les événements
        $this->generateRecurringEvents($parentEvent);
    }

    /**
     * Supprime une occurrence et toutes les occurrences suivantes d'un événement récurrent
     */
    public function deleteFromOccurrence(Event $occurrence): int
    {
        if (!$occurrence->isGeneratedEvent()) {
            throw new \InvalidArgumentException('L\'événement fourni doit être une occurrence générée.');
        }

        $parentEvent = $occurrence->getParentEvent();
        $occurrenceDate = $occurrence->getStartDate();
        $deletedCount = 0;

        // Supprimer l'occurrence actuelle et toutes les suivantes
        $generatedEvents = $parentEvent->getGeneratedEvents()->toArray();
        
        foreach ($generatedEvents as $generatedEvent) {
            if ($generatedEvent->getStartDate() >= $occurrenceDate) {
                $this->entityManager->remove($generatedEvent);
                $parentEvent->removeGeneratedEvent($generatedEvent);
                $deletedCount++;
            }
        }

        // Si on a supprimé des événements, il faut aussi mettre à jour la date de fin de récurrence
        // du parent pour éviter que de nouveaux événements soient générés
        if ($deletedCount > 0) {
            // Trouver la dernière occurrence qui reste (si il y en a)
            $remainingEvents = [];
            foreach ($generatedEvents as $generatedEvent) {
                if ($generatedEvent->getStartDate() < $occurrenceDate) {
                    $remainingEvents[] = $generatedEvent;
                }
            }

            if (!empty($remainingEvents)) {
                // Trier par date et prendre le dernier
                usort($remainingEvents, fn($a, $b) => $a->getStartDate() <=> $b->getStartDate());
                $lastEvent = end($remainingEvents);
                
                // Ajuster la date de fin de récurrence du parent
                $newEndDate = (clone $lastEvent->getStartDate())->modify('-1 day');
                $parentEvent->setRecurrenceEndDate($newEndDate);
            } else {
                // Plus d'événements générés, arrêter la récurrence complètement
                $parentEvent->setRecurring(false);
                $parentEvent->setRecurrenceEndDate(null);
                $parentEvent->setRecurrenceType(null);
                $parentEvent->setRecurrenceInterval(null);
                $parentEvent->setRecurrenceWeekdays(null);
            }
        }

        $this->entityManager->flush();
        
        return $deletedCount;
    }
}