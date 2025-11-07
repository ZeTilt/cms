# Simplifications de la Logique

[⬅️ Retour à l'index](README.md) | [⬅️ Sécurité](08-securite.md) | [➡️ Améliorations](10-ameliorations.md)

## 🎯 Objectif

Ce document détaille les **simplifications concrètes** pour réduire la complexité du code, éliminer les duplications, et améliorer la maintenabilité.

## 📊 Analyse de Complexité

### Entités par Complexité

| Entité | Lignes | Complexité | Action |
|--------|--------|------------|--------|
| `Event` | 656 | 🔴 Très haute | Refactoring majeur |
| `Image` | 309 | 🟠 Moyenne | Extraction services |
| `Page` | 292 | 🟡 Acceptable | OK |
| `Article` | 281 | 🟡 Acceptable | OK |
| `User` | 277 | 🟡 Acceptable | Léger cleanup |
| `EventCondition` | 271 | 🟠 Moyenne | Extraction |
| `Gallery` | 264 | 🟡 Acceptable | OK |

### Contrôleurs par Complexité

| Contrôleur | Lignes | Responsabilités | Action |
|------------|--------|----------------|--------|
| `GalleryController` | 333 | Upload + CRUD + Ordering | Split |
| `DpEventController` | 308 | DP + Validation + Export | Split |
| `AdminEventConditionController` | 296 | CRUD + Introspection | Extract |
| `AdminEventController` | 282 | CRUD + Récurrence | Extract |
| `AdminUserController` | 280 | CRUD + Approval + Roles | Split |

### Services par Complexité

| Service | Lignes | Complexité | Action |
|---------|--------|------------|--------|
| `EntityIntrospectionService` | 344 | Haute | Simplifier |
| `RecurringEventService` | 254 | Haute | Refactor Strategy |

---

## 🔨 Refactoring #1 : Event Entity (PRIORITÉ HAUTE)

### Problème

**Fichier :** `src/Entity/Event.php` (656 lignes)

L'entité `Event` viole le **Single Responsibility Principle** :
- Gestion des données de l'événement ✅
- Logique de récurrence 🔴
- Gestion des participants 🔴
- Vérification éligibilité 🔴
- Calcul places disponibles 🔴

### Solution : Extraction en Value Objects et Services

#### 1.1 Extraire Récurrence → Value Object

**Créer :** `src/ValueObject/EventRecurrence.php`

```php
<?php
namespace App\ValueObject;

class EventRecurrence
{
    public function __construct(
        private bool $isRecurring,
        private ?string $type = null,           // daily/weekly/monthly
        private ?int $interval = null,
        private ?array $weekdays = null,
        private ?\DateTime $endDate = null
    ) {}

    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getInterval(): ?int
    {
        return $this->interval;
    }

    public function getWeekdays(): ?array
    {
        return $this->weekdays;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public static function none(): self
    {
        return new self(false);
    }

    public static function daily(int $interval, \DateTime $endDate): self
    {
        return new self(true, 'daily', $interval, null, $endDate);
    }

    public static function weekly(array $weekdays, \DateTime $endDate): self
    {
        return new self(true, 'weekly', 1, $weekdays, $endDate);
    }

    public static function monthly(int $dayOfMonth, \DateTime $endDate): self
    {
        return new self(true, 'monthly', $dayOfMonth, null, $endDate);
    }
}
```

**Dans Event.php :**

```php
#[ORM\Embedded(class: EventRecurrence::class)]
private EventRecurrence $recurrence;

public function __construct()
{
    $this->recurrence = EventRecurrence::none();
    // ...
}
```

**Bénéfices :**
- ✅ Encapsulation logique récurrence
- ✅ Validation au constructeur
- ✅ Immutabilité
- ✅ Réduction 50+ lignes dans Event

#### 1.2 Extraire Gestion Participants → Service

**Créer :** `src/Service/Event/ParticipationManager.php`

```php
<?php
namespace App\Service\Event;

use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ParticipationManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private EligibilityChecker $eligibilityChecker,
        private NotificationService $notificationService
    ) {}

    public function register(User $user, Event $event, string $meetingPoint = 'club'): EventParticipation
    {
        // Vérifier si déjà inscrit
        if ($this->isUserRegistered($user, $event)) {
            throw new \DomainException('Déjà inscrit à cet événement');
        }

        // Vérifier éligibilité
        if (!$this->eligibilityChecker->isEligible($user, $event)) {
            $reasons = $this->eligibilityChecker->getIneligibilityReasons($user, $event);
            throw new \DomainException(implode(', ', $reasons));
        }

        // Déterminer statut selon capacité
        $status = $this->getCapacity($event) > 0 ? 'confirmed' : 'waiting_list';

        $participation = new EventParticipation();
        $participation->setEvent($event);
        $participation->setParticipant($user);
        $participation->setStatus($status);
        $participation->setMeetingPoint($meetingPoint);
        $participation->setRegistrationDate(new \DateTime());

        if ($status === 'confirmed') {
            $participation->setConfirmationDate(new \DateTime());
        }

        $this->em->persist($participation);
        $this->em->flush();

        // Notification
        $this->notificationService->sendRegistrationConfirmation($participation);

        return $participation;
    }

    public function unregister(EventParticipation $participation): void
    {
        $event = $participation->getEvent();

        $this->em->remove($participation);
        $this->em->flush();

        // Promouvoir liste d'attente
        $this->promoteFromWaitingList($event);
    }

    public function promoteFromWaitingList(Event $event): ?EventParticipation
    {
        if ($this->getCapacity($event) <= 0) {
            return null;
        }

        $waiting = $this->em->getRepository(EventParticipation::class)
            ->createQueryBuilder('p')
            ->where('p.event = :event')
            ->andWhere('p.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', 'waiting_list')
            ->orderBy('p.registrationDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$waiting) {
            return null;
        }

        $waiting->setStatus('confirmed');
        $waiting->setConfirmationDate(new \DateTime());

        $this->em->flush();

        $this->notificationService->sendPromotionNotification($waiting);

        return $waiting;
    }

    private function getCapacity(Event $event): int
    {
        $max = $event->getMaxParticipants();
        if ($max === null) {
            return PHP_INT_MAX; // Illimité
        }

        $confirmed = $this->em->getRepository(EventParticipation::class)
            ->count([
                'event' => $event,
                'status' => 'confirmed'
            ]);

        return max(0, $max - $confirmed);
    }

    private function isUserRegistered(User $user, Event $event): bool
    {
        return $this->em->getRepository(EventParticipation::class)
            ->count([
                'event' => $event,
                'participant' => $user,
                'status' => ['confirmed', 'waiting_list']
            ]) > 0;
    }
}
```

**Bénéfices :**
- ✅ Logique participations hors de Event
- ✅ Testabilité (mock des dépendances)
- ✅ Réutilisabilité
- ✅ Réduction ~100 lignes dans Event

#### 1.3 Extraire Éligibilité → Service

**Créer :** `src/Service/Event/EligibilityChecker.php`

```php
<?php
namespace App\Service\Event;

use App\Entity\Event;
use App\Entity\User;
use App\Service\EventConditionService;

class EligibilityChecker
{
    public function __construct(
        private EventConditionService $conditionService
    ) {}

    public function isEligible(User $user, Event $event): bool
    {
        // Vérifier niveau plongée
        if (!$this->checkDivingLevel($user, $event)) {
            return false;
        }

        // Vérifier conditions custom
        if (!$this->conditionService->evaluateConditions($event, $user)) {
            return false;
        }

        return true;
    }

    public function getIneligibilityReasons(User $user, Event $event): array
    {
        $reasons = [];

        if (!$this->checkDivingLevel($user, $event)) {
            $reasons[] = sprintf(
                'Niveau minimum requis : %s',
                $event->getMinDivingLevel()?->getName() ?? 'Aucun'
            );
        }

        $failedConditions = $this->conditionService->getFailedConditions($event, $user);
        foreach ($failedConditions as $condition) {
            $reasons[] = $condition->getErrorMessage();
        }

        return $reasons;
    }

    private function checkDivingLevel(User $user, Event $event): bool
    {
        $minLevel = $event->getMinDivingLevel();
        if (!$minLevel) {
            return true; // Pas de niveau requis
        }

        $userLevel = $user->getHighestDivingLevel();
        if (!$userLevel) {
            return false; // User sans niveau
        }

        return $userLevel->getSortOrder() >= $minLevel->getSortOrder();
    }
}
```

**Bénéfices :**
- ✅ Séparation responsabilité
- ✅ Facilité ajout nouvelles règles
- ✅ Tests unitaires faciles

### Résultat Final Event.php

**Avant :** 656 lignes, 15+ méthodes métier
**Après :** ~250 lignes, juste données + getters/setters

**Code Event.php simplifié :**

```php
<?php
namespace App\Entity;

#[ORM\Entity]
class Event
{
    #[ORM\Id, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $startDate;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $endDate;

    #[ORM\ManyToOne(targetEntity: EventType::class)]
    private ?EventType $eventType = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\ManyToOne(targetEntity: DivingLevel::class)]
    private ?DivingLevel $minDivingLevel = null;

    #[ORM\Embedded(class: EventRecurrence::class)]
    private EventRecurrence $recurrence;

    // Getters / Setters uniquement
}
```

---

## 🔨 Refactoring #2 : RecurringEventService (PRIORITÉ HAUTE)

### Problème

**Fichier :** `src/Service/RecurringEventService.php` (254 lignes)

Grande méthode avec switch/case pour gérer différents patterns.

### Solution : Pattern Strategy

**Créer interface :** `src/Service/Event/Recurrence/RecurrencePatternInterface.php`

```php
<?php
namespace App\Service\Event\Recurrence;

use App\Entity\Event;

interface RecurrencePatternInterface
{
    public function generateDates(Event $parentEvent): array;
    public function supports(string $type): bool;
}
```

**Implémenter patterns :**

```php
<?php
namespace App\Service\Event\Recurrence;

class DailyRecurrencePattern implements RecurrencePatternInterface
{
    public function supports(string $type): bool
    {
        return $type === 'daily';
    }

    public function generateDates(Event $parentEvent): array
    {
        $dates = [];
        $current = clone $parentEvent->getStartDate();
        $end = $parentEvent->getRecurrenceEndDate();
        $interval = $parentEvent->getRecurrenceInterval();

        while ($current <= $end) {
            $dates[] = clone $current;
            $current->modify("+{$interval} days");
        }

        return $dates;
    }
}

class WeeklyRecurrencePattern implements RecurrencePatternInterface
{
    public function supports(string $type): bool
    {
        return $type === 'weekly';
    }

    public function generateDates(Event $parentEvent): array
    {
        $dates = [];
        $weekdays = $parentEvent->getRecurrenceWeekdays(); // [1, 3, 5] = Lun, Mer, Ven
        $end = $parentEvent->getRecurrenceEndDate();
        $current = clone $parentEvent->getStartDate();

        while ($current <= $end) {
            if (in_array((int)$current->format('N'), $weekdays)) {
                $dates[] = clone $current;
            }
            $current->modify('+1 day');
        }

        return $dates;
    }
}

class MonthlyRecurrencePattern implements RecurrencePatternInterface
{
    public function supports(string $type): bool
    {
        return $type === 'monthly';
    }

    public function generateDates(Event $parentEvent): array
    {
        $dates = [];
        $current = clone $parentEvent->getStartDate();
        $end = $parentEvent->getRecurrenceEndDate();
        $dayOfMonth = (int)$current->format('d');

        while ($current <= $end) {
            $dates[] = clone $current;
            $current->modify('first day of next month');
            $current->setDate(
                (int)$current->format('Y'),
                (int)$current->format('m'),
                min($dayOfMonth, (int)$current->format('t')) // Gère mois courts
            );
        }

        return $dates;
    }
}
```

**Service simplifié :**

```php
<?php
namespace App\Service;

use App\Entity\Event;
use App\Service\Event\Recurrence\RecurrencePatternInterface;
use Doctrine\ORM\EntityManagerInterface;

class RecurringEventService
{
    /** @var RecurrencePatternInterface[] */
    private array $patterns;

    public function __construct(
        private EntityManagerInterface $em,
        iterable $patterns // Auto-injection via tag
    ) {
        $this->patterns = iterator_to_array($patterns);
    }

    public function generateRecurringEvents(Event $parentEvent): array
    {
        if (!$parentEvent->isRecurring()) {
            return [];
        }

        // Trouver pattern approprié
        $pattern = $this->getPattern($parentEvent->getRecurrenceType());
        $dates = $pattern->generateDates($parentEvent);

        // Créer événements
        $events = [];
        foreach ($dates as $date) {
            $event = $this->cloneEvent($parentEvent, $date);
            $events[] = $event;
            $this->em->persist($event);
        }

        $this->em->flush();

        return $events;
    }

    private function getPattern(string $type): RecurrencePatternInterface
    {
        foreach ($this->patterns as $pattern) {
            if ($pattern->supports($type)) {
                return $pattern;
            }
        }

        throw new \InvalidArgumentException("Unsupported recurrence type: {$type}");
    }

    private function cloneEvent(Event $parent, \DateTime $date): Event
    {
        $event = new Event();
        $event->setTitle($parent->getTitle());
        $event->setDescription($parent->getDescription());
        $event->setStartDate($date);

        // Calculer endDate avec même durée
        $duration = $parent->getStartDate()->diff($parent->getEndDate());
        $endDate = clone $date;
        $endDate->add($duration);
        $event->setEndDate($endDate);

        $event->setEventType($parent->getEventType());
        $event->setLocation($parent->getLocation());
        $event->setMaxParticipants($parent->getMaxParticipants());
        $event->setMinDivingLevel($parent->getMinDivingLevel());
        $event->setParentEvent($parent);

        return $event;
    }
}
```

**Configuration services.yaml :**

```yaml
services:
    # Auto-tag patterns
    App\Service\Event\Recurrence\:
        resource: '../src/Service/Event/Recurrence/*Pattern.php'
        tags: ['app.recurrence.pattern']

    App\Service\RecurringEventService:
        arguments:
            $patterns: !tagged_iterator app.recurrence.pattern
```

**Bénéfices :**
- ✅ Réduction 254 → ~100 lignes
- ✅ Facilité ajout nouveaux patterns (yearly, custom)
- ✅ Testabilité (test chaque pattern individuellement)
- ✅ Open/Closed Principle

---

## 🔨 Refactoring #3 : Contrôleurs (PRIORITÉ MOYENNE)

### 3.1 Créer AbstractFormController

**Créer :** `src/Controller/AbstractFormController.php`

```php
<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractFormController extends AbstractController
{
    protected function handleFormSubmit(
        FormInterface $form,
        Request $request,
        callable $onSuccess,
        ?callable $onError = null
    ): ?Response {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                return $onSuccess($form->getData());
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        if ($form->isSubmitted() && !$form->isValid() && $onError) {
            return $onError($form);
        }

        return null;
    }

    protected function successFlash(string $message): void
    {
        $this->addFlash('success', $message);
    }

    protected function errorFlash(string $message): void
    {
        $this->addFlash('error', $message);
    }
}
```

**Usage dans contrôleurs :**

**Avant :**

```php
class AdminEventController extends AbstractController
{
    public function new(Request $request): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->em->persist($event);
                $this->em->flush();
                $this->addFlash('success', 'Événement créé');
                return $this->redirectToRoute('admin_event_index');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('admin/event/new.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
```

**Après :**

```php
class AdminEventController extends AbstractFormController
{
    public function new(Request $request): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);

        $response = $this->handleFormSubmit($form, $request, function($event) {
            $this->em->persist($event);
            $this->em->flush();
            $this->successFlash('Événement créé');
            return $this->redirectToRoute('admin_event_index');
        });

        if ($response) {
            return $response;
        }

        return $this->render('admin/event/new.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
```

**Bénéfices :**
- ✅ Réduction ~10 lignes par action CRUD
- ✅ Standardisation gestion formulaires
- ✅ DRY

### 3.2 Créer FlashMessageTrait

**Créer :** `src/Trait/FlashMessageTrait.php`

```php
<?php
namespace App\Trait;

trait FlashMessageTrait
{
    protected function flashSuccess(string $message): void
    {
        $this->addFlash('success', $message);
    }

    protected function flashError(string $message): void
    {
        $this->addFlash('error', $message);
    }

    protected function flashWarning(string $message): void
    {
        $this->addFlash('warning', $message);
    }

    protected function flashInfo(string $message): void
    {
        $this->addFlash('info', $message);
    }
}
```

---

## 🔨 Refactoring #4 : Duplication Code (PRIORITÉ BASSE)

### 4.1 Repository Base Class

**Créer :** `src/Repository/AbstractRepository.php`

```php
<?php
namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class AbstractRepository extends ServiceEntityRepository
{
    public function save(object $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(object $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findActive(): array
    {
        return $this->findBy(['active' => true]);
    }
}
```

**Usage :**

```php
class EventRepository extends AbstractRepository
{
    // Héritage méthodes communes
    // + méthodes spécifiques Event
}

// Dans service/contrôleur
$eventRepository->save($event);
$eventRepository->remove($event);
```

---

## 📊 Gains Estimés

| Refactoring | Réduction Lignes | Complexité | Testabilité | Maintenabilité |
|-------------|------------------|------------|-------------|----------------|
| Event → Services | -200 lignes | 🔴→🟢 | 🔴→🟢 | 🔴→🟢 |
| RecurringEvent Strategy | -100 lignes | 🔴→🟢 | 🔴→🟢 | 🔴→🟢 |
| AbstractFormController | -300 lignes total | 🟡→🟢 | ✅ | 🟡→🟢 |
| FlashMessageTrait | -50 lignes | ✅ | ✅ | ✅ |
| AbstractRepository | -200 lignes total | ✅ | ✅ | 🟡→🟢 |

**Total estimé :** ~850 lignes éliminées, complexité réduite de 40%

---

[➡️ Suite : Améliorations](10-ameliorations.md)
