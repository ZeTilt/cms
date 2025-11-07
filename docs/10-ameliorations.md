# Améliorations Recommandées

[⬅️ Retour à l'index](README.md) | [⬅️ Simplifications](09-simplifications.md) | [➡️ Dette Technique](11-dette-technique.md)

## 🎯 Objectif

Ce document détaille les **améliorations fonctionnelles et techniques** pour enrichir l'application, optimiser les performances, et améliorer l'expérience utilisateur.

---

## 📧 Amélioration #1 : Système de Notifications Email (PRIORITÉ HAUTE)

### Problème Actuel

Infrastructure Mailer configurée mais emails non envoyés.

### Solution

**1. Configurer Mailer**

```yaml
# config/packages/mailer.yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'

# .env
MAILER_DSN=smtp://user:pass@smtp.example.com:587
# Ou pour développement:
MAILER_DSN=null://null
```

**2. Créer NotificationService**

```php
<?php
namespace App\Service;

use App\Entity\EventParticipation;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail = 'noreply@venetes.fr',
        private string $fromName = 'Club Vénètes'
    ) {}

    public function sendRegistrationConfirmation(EventParticipation $participation): void
    {
        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($participation->getParticipant()->getEmail())
            ->subject('Inscription confirmée - ' . $participation->getEvent()->getTitle())
            ->htmlTemplate('emails/registration_confirmed.html.twig')
            ->context([
                'participation' => $participation,
                'event' => $participation->getEvent(),
                'user' => $participation->getParticipant()
            ]);

        $this->mailer->send($email);
    }

    public function sendWaitingListNotification(EventParticipation $participation): void
    {
        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($participation->getParticipant()->getEmail())
            ->subject('Liste d\'attente - ' . $participation->getEvent()->getTitle())
            ->htmlTemplate('emails/waiting_list.html.twig')
            ->context([
                'participation' => $participation,
                'event' => $participation->getEvent()
            ]);

        $this->mailer->send($email);
    }

    public function sendPromotionNotification(EventParticipation $participation): void
    {
        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($participation->getParticipant()->getEmail())
            ->subject('Place disponible ! - ' . $participation->getEvent()->getTitle())
            ->htmlTemplate('emails/promoted_from_waiting_list.html.twig')
            ->context([
                'participation' => $participation,
                'event' => $participation->getEvent()
            ]);

        $this->mailer->send($email);
    }

    public function sendEventReminder(EventParticipation $participation): void
    {
        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($participation->getParticipant()->getEmail())
            ->subject('Rappel : ' . $participation->getEvent()->getTitle())
            ->htmlTemplate('emails/event_reminder.html.twig')
            ->context([
                'participation' => $participation,
                'event' => $participation->getEvent()
            ]);

        $this->mailer->send($email);
    }

    public function sendAccountApproved(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Compte approuvé - Club Vénètes')
            ->htmlTemplate('emails/account_approved.html.twig')
            ->context(['user' => $user]);

        $this->mailer->send($email);
    }

    public function sendAccountRejected(User $user, string $reason): void
    {
        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Demande de compte - Club Vénètes')
            ->htmlTemplate('emails/account_rejected.html.twig')
            ->context(['user' => $user, 'reason' => $reason]);

        $this->mailer->send($email);
    }
}
```

**3. Templates Email**

```twig
{# templates/emails/registration_confirmed.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background-color: #2563EB; color: white; padding: 20px; }
        .content { padding: 20px; }
        .button {
            background-color: #2563EB;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Inscription Confirmée</h1>
    </div>
    <div class="content">
        <p>Bonjour {{ user.firstName }},</p>

        <p>Votre inscription à l'événement <strong>{{ event.title }}</strong> est confirmée.</p>

        <h3>Détails de l'événement :</h3>
        <ul>
            <li><strong>Date :</strong> {{ event.startDate|date('d/m/Y à H:i') }}</li>
            <li><strong>Lieu :</strong> {{ event.location }}</li>
            {% if participation.meetingPoint == 'club' %}
                <li><strong>RDV :</strong> Au club à {{ event.clubMeetingTime|date('H:i') }}</li>
            {% else %}
                <li><strong>RDV :</strong> Sur site à {{ event.siteMeetingTime|date('H:i') }}</li>
            {% endif %}
        </ul>

        <p>
            <a href="{{ url('calendar_event_show', {id: event.id}) }}" class="button">
                Voir les détails
            </a>
        </p>

        <p>À bientôt dans l'eau !</p>
        <p>L'équipe du Club Vénètes</p>
    </div>
</body>
</html>
```

**4. Commande Rappels Automatiques**

```php
<?php
namespace App\Command;

use App\Repository\EventParticipationRepository;
use App\Service\NotificationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:send-event-reminders')]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private EventParticipationRepository $participationRepo,
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Événements dans les prochaines 48h
        $tomorrow = new \DateTime('+48 hours');
        $participations = $this->participationRepo->findUpcomingParticipations($tomorrow);

        $sent = 0;
        foreach ($participations as $participation) {
            try {
                $this->notificationService->sendEventReminder($participation);
                $sent++;
            } catch (\Exception $e) {
                $output->writeln("Erreur : " . $e->getMessage());
            }
        }

        $output->writeln("$sent rappels envoyés");

        return Command::SUCCESS;
    }
}
```

**5. Cron Job**

```bash
# crontab -e
0 8 * * * cd /var/www/mon-site-plongee && php bin/console app:send-event-reminders
```

**Bénéfices :**
- ✅ Communication automatisée
- ✅ Meilleure engagement membres
- ✅ Moins d'oublis
- ✅ Expérience professionnelle

---

## 📄 Amélioration #2 : Export & Impression (PRIORITÉ HAUTE)

### 2.1 Export PDF Liste Participants

**Installation :**

```bash
composer require dompdf/dompdf
```

**Service :**

```php
<?php
namespace App\Service;

use App\Entity\Event;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class ParticipantExporter
{
    public function __construct(
        private Environment $twig
    ) {}

    public function exportToPDF(Event $event): string
    {
        $html = $this->twig->render('admin/event/participants_pdf.html.twig', [
            'event' => $event,
            'participations' => $event->getConfirmedParticipations()
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf(
            'participants_%s_%s.pdf',
            $event->getSlug() ?? $event->getId(),
            (new \DateTime())->format('Y-m-d')
        );

        return $dompdf->output();
    }

    public function exportToCSV(Event $event): string
    {
        $csv = "Nom,Prénom,Email,Niveau,Point RDV\n";

        foreach ($event->getConfirmedParticipations() as $p) {
            $user = $p->getParticipant();
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s"' . "\n",
                $user->getLastName(),
                $user->getFirstName(),
                $user->getEmail(),
                $user->getHighestDivingLevel()?->getCode() ?? 'N/A',
                $p->getMeetingPoint() === 'club' ? 'Club' : 'Site'
            );
        }

        return $csv;
    }
}
```

**Contrôleur :**

```php
#[Route('/dp/events/{id}/export/{format}', name: 'dp_event_export')]
public function export(
    Event $event,
    string $format,
    ParticipantExporter $exporter
): Response {
    return match($format) {
        'pdf' => new Response(
            $exporter->exportToPDF($event),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="participants.pdf"'
            ]
        ),
        'csv' => new Response(
            $exporter->exportToCSV($event),
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="participants.csv"'
            ]
        ),
        default => throw new \InvalidArgumentException('Format non supporté')
    };
}
```

### 2.2 Export iCal Calendrier

**Service :**

```php
<?php
namespace App\Service;

use App\Entity\Event;
use App\Repository\EventRepository;

class CalendarExporter
{
    public function exportToICal(array $events): string
    {
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Club Vénètes//NONSGML Events//FR\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";

        foreach ($events as $event) {
            $ical .= $this->eventToICal($event);
        }

        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    private function eventToICal(Event $event): string
    {
        $ical = "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . $event->getId() . "@venetes.fr\r\n";
        $ical .= "DTSTAMP:" . (new \DateTime())->format('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . $event->getStartDate()->format('Ymd\THis\Z') . "\r\n";
        $ical .= "DTEND:" . $event->getEndDate()->format('Ymd\THis\Z') . "\r\n";
        $ical .= "SUMMARY:" . $this->escape($event->getTitle()) . "\r\n";
        $ical .= "DESCRIPTION:" . $this->escape(strip_tags($event->getDescription() ?? '')) . "\r\n";
        $ical .= "LOCATION:" . $this->escape($event->getLocation() ?? '') . "\r\n";
        $ical .= "END:VEVENT\r\n";

        return $ical;
    }

    private function escape(string $text): string
    {
        return str_replace(["\n", "\r"], ['\\n', ''], $text);
    }
}
```

**Route :**

```php
#[Route('/calendrier/export.ics', name: 'calendar_export')]
public function exportCalendar(
    EventRepository $eventRepo,
    CalendarExporter $exporter
): Response {
    $events = $eventRepo->findPublishedEvents();
    $ical = $exporter->exportToICal($events);

    return new Response($ical, 200, [
        'Content-Type' => 'text/calendar; charset=utf-8',
        'Content-Disposition' => 'attachment; filename="club-venetes.ics"'
    ]);
}
```

---

## 📊 Amélioration #3 : Statistiques et Tableau de Bord (PRIORITÉ MOYENNE)

**Service :**

```php
<?php
namespace App\Service;

use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Repository\EventParticipationRepository;

class StatisticsService
{
    public function __construct(
        private EventRepository $eventRepo,
        private UserRepository $userRepo,
        private EventParticipationRepository $participationRepo
    ) {}

    public function getClubStats(): array
    {
        return [
            'total_members' => $this->userRepo->count(['active' => true]),
            'pending_approvals' => $this->userRepo->count(['status' => 'pending']),
            'upcoming_events' => $this->eventRepo->countUpcoming(),
            'total_events_this_month' => $this->eventRepo->countThisMonth(),
            'total_participations_this_month' => $this->participationRepo->countThisMonth(),
            'average_participants_per_event' => $this->participationRepo->getAverageParticipants(),
            'most_popular_event_type' => $this->eventRepo->getMostPopularType(),
            'members_by_level' => $this->userRepo->countByDivingLevel()
        ];
    }

    public function getUserStats(int $userId): array
    {
        return [
            'total_participations' => $this->participationRepo->countByUser($userId),
            'upcoming_events' => $this->participationRepo->findUpcomingByUser($userId),
            'past_events' => $this->participationRepo->findPastByUser($userId),
            'favorite_event_type' => $this->participationRepo->getFavoriteEventType($userId),
            'attendance_rate' => $this->participationRepo->getAttendanceRate($userId)
        ];
    }

    public function getEventStats(int $eventId): array
    {
        $event = $this->eventRepo->find($eventId);

        return [
            'total_registered' => $this->participationRepo->count(['event' => $eventId]),
            'confirmed' => $this->participationRepo->count(['event' => $eventId, 'status' => 'confirmed']),
            'waiting_list' => $this->participationRepo->count(['event' => $eventId, 'status' => 'waiting_list']),
            'cancelled' => $this->participationRepo->count(['event' => $eventId, 'status' => 'cancelled']),
            'by_level' => $this->participationRepo->countByLevelForEvent($eventId),
            'by_meeting_point' => $this->participationRepo->countByMeetingPoint($eventId)
        ];
    }
}
```

**Dashboard Admin :**

```twig
{# templates/admin/dashboard.html.twig #}
{% extends 'admin/base.html.twig' %}

{% block body %}
<h1>Tableau de Bord</h1>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-6 rounded shadow">
        <h3 class="text-gray-500 text-sm">Membres Actifs</h3>
        <p class="text-3xl font-bold">{{ stats.total_members }}</p>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <h3 class="text-gray-500 text-sm">Approbations en Attente</h3>
        <p class="text-3xl font-bold text-yellow-600">{{ stats.pending_approvals }}</p>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <h3 class="text-gray-500 text-sm">Événements à Venir</h3>
        <p class="text-3xl font-bold">{{ stats.upcoming_events }}</p>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <h3 class="text-gray-500 text-sm">Participations ce Mois</h3>
        <p class="text-3xl font-bold text-green-600">{{ stats.total_participations_this_month }}</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-white p-6 rounded shadow">
        <h3 class="text-lg font-bold mb-4">Répartition par Niveau</h3>
        <canvas id="levels-chart"></canvas>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <h3 class="text-lg font-bold mb-4">Événements par Type</h3>
        <canvas id="events-chart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphiques Chart.js
</script>
{% endblock %}
```

---

## 🔍 Amélioration #4 : Recherche Avancée (PRIORITÉ MOYENNE)

**Service :**

```php
<?php
namespace App\Service;

use App\Repository\EventRepository;
use Doctrine\ORM\QueryBuilder;

class EventSearchService
{
    public function __construct(
        private EventRepository $eventRepo
    ) {}

    public function search(array $criteria): array
    {
        $qb = $this->eventRepo->createQueryBuilder('e');

        if (!empty($criteria['query'])) {
            $qb->andWhere('e.title LIKE :query OR e.description LIKE :query')
               ->setParameter('query', '%' . $criteria['query'] . '%');
        }

        if (!empty($criteria['type'])) {
            $qb->andWhere('e.eventType = :type')
               ->setParameter('type', $criteria['type']);
        }

        if (!empty($criteria['min_level'])) {
            $qb->join('e.minDivingLevel', 'dl')
               ->andWhere('dl.sortOrder >= :min_level')
               ->setParameter('min_level', $criteria['min_level']);
        }

        if (!empty($criteria['date_from'])) {
            $qb->andWhere('e.startDate >= :from')
               ->setParameter('from', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $qb->andWhere('e.startDate <= :to')
               ->setParameter('to', $criteria['date_to']);
        }

        if (isset($criteria['available_only']) && $criteria['available_only']) {
            $qb->leftJoin('e.participations', 'p', 'WITH', 'p.status = :confirmed')
               ->setParameter('confirmed', 'confirmed')
               ->groupBy('e.id')
               ->having('COUNT(p.id) < e.maxParticipants OR e.maxParticipants IS NULL');
        }

        return $qb->orderBy('e.startDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
}
```

---

## 🚀 Amélioration #5 : Performance (PRIORITÉ MOYENNE)

### 5.1 Index Database

```php
// migrations/VersionXXX.php
public function up(Schema $schema): void
{
    $this->addSql('CREATE INDEX idx_event_start_date ON event (start_date)');
    $this->addSql('CREATE INDEX idx_event_status ON event (status)');
    $this->addSql('CREATE INDEX idx_user_status ON user (status)');
    $this->addSql('CREATE INDEX idx_user_email_verified ON user (email_verified)');
    $this->addSql('CREATE INDEX idx_participation_status ON event_participation (status)');
    $this->addSql('CREATE INDEX idx_entity_attribute_lookup ON entity_attribute (entity_type, entity_id)');
}
```

### 5.2 Query Optimization

**Avant (N+1 queries) :**

```php
$events = $eventRepo->findAll();
foreach ($events as $event) {
    echo $event->getEventType()->getName(); // Query à chaque fois !
}
```

**Après :**

```php
$events = $eventRepo->createQueryBuilder('e')
    ->leftJoin('e.eventType', 'et')
    ->addSelect('et')
    ->getQuery()
    ->getResult();

foreach ($events as $event) {
    echo $event->getEventType()->getName(); // Déjà chargé
}
```

### 5.3 HTTP Caching

```php
#[Route('/calendrier', name: 'calendar_index')]
public function index(Request $request): Response
{
    $response = new Response();
    $response->setPublic();
    $response->setMaxAge(3600); // 1 heure
    $response->headers->addCacheControlDirective('must-revalidate', true);

    // ETag based on last event modification
    $lastModified = $this->eventRepo->getLastModifiedDate();
    $response->setLastModified($lastModified);

    if ($response->isNotModified($request)) {
        return $response;
    }

    // Render normal
    return $this->render('calendar/index.html.twig', [
        'events' => $this->eventRepo->findPublishedEvents()
    ], $response);
}
```

---

## 📱 Amélioration #6 : Progressive Web App (PRIORITÉ BASSE)

**manifest.json (déjà présent, compléter) :**

```json
{
  "name": "Club Subaquatique des Vénètes",
  "short_name": "Club Vénètes",
  "description": "Gestion des activités du club de plongée",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#2563EB",
  "icons": [
    {
      "src": "/images/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/images/icon-512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

**Service Worker (public/sw.js) :**

```javascript
const CACHE_NAME = 'venetes-v1';
const urlsToCache = [
  '/',
  '/calendrier',
  '/css/app.css',
  '/js/app.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});
```

---

## 🌍 Amélioration #7 : Internationalisation (PRIORITÉ BASSE)

**Installation :**

```bash
composer require symfony/translation
```

**Configuration :**

```yaml
# config/packages/translation.yaml
framework:
    default_locale: fr
    translator:
        default_path: '%kernel.project_dir%/translations'
        fallbacks:
            - fr
```

**Traductions :**

```yaml
# translations/messages.fr.yaml
event:
    title: "Événement"
    create: "Créer un événement"
    edit: "Modifier l'événement"

# translations/messages.en.yaml
event:
    title: "Event"
    create: "Create event"
    edit: "Edit event"
```

**Usage :**

```twig
{{ 'event.create'|trans }}
```

---

## 📊 Récapitulatif des Améliorations

| Amélioration | Priorité | Effort | Impact | ROI |
|--------------|----------|--------|--------|-----|
| Notifications Email | 🔴 Haute | Moyen | Très Élevé | ⭐⭐⭐⭐⭐ |
| Export PDF/CSV | 🔴 Haute | Faible | Élevé | ⭐⭐⭐⭐⭐ |
| Statistiques | 🟠 Moyenne | Moyen | Élevé | ⭐⭐⭐⭐ |
| Recherche Avancée | 🟠 Moyenne | Faible | Moyen | ⭐⭐⭐ |
| Performance | 🟠 Moyenne | Moyen | Élevé | ⭐⭐⭐⭐ |
| PWA | 🟡 Basse | Élevé | Moyen | ⭐⭐ |
| i18n | 🟡 Basse | Élevé | Faible | ⭐ |

---

[➡️ Suite : Dette Technique](11-dette-technique.md)
