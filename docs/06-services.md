# Couche Service

[⬅️ Retour à l'index](README.md) | [⬅️ Contrôleurs](05-controleurs-routes.md) | [➡️ Interface](07-interface-utilisateur.md)

## 🔧 Vue d'Ensemble

La couche service contient la logique métier de l'application, séparée des contrôleurs pour respecter le principe de responsabilité unique.

**Total : 14+ services**

## 📋 Services Principaux

### 1. RecurringEventService (254 lignes)

**Fichier :** `src/Service/RecurringEventService.php`
**Responsabilité :** Génération d'événements récurrents

**Méthodes clés :**

```php
public function generateRecurringEvents(Event $parentEvent): array
public function deleteFromDate(Event $parentEvent, \DateTime $fromDate): void
public function updateRecurringSeries(Event $parentEvent): void
```

**Algorithme de génération :**

```php
1. Valider que l'événement est récurrent
2. Déterminer type (daily/weekly/monthly)
3. Calculer toutes les dates selon pattern
4. Pour chaque date :
   - Cloner l'événement parent
   - Ajuster startDate et endDate
   - Lier au parent via parentEvent
   - Persister
5. Flush en batch
```

**Types de récurrence :**

| Type | Logique | Exemple |
|------|---------|---------|
| **daily** | Tous les X jours | Tous les 2 jours |
| **weekly** | Jours spécifiques | Lun, Mer, Ven chaque semaine |
| **monthly** | Même jour du mois | Le 15 de chaque mois |

**Complexité :** ⚠️ Moyenne-Haute (254 lignes)
**Amélioration possible :** Extraire patterns en classes Strategy

---

### 2. EventConditionService

**Fichier :** `src/Service/EventConditionService.php`
**Responsabilité :** Évaluation des conditions d'éligibilité

**Méthodes clés :**

```php
public function evaluateConditions(Event $event, User $user): bool
public function getFailedConditions(Event $event, User $user): array
```

**Processus d'évaluation :**

```php
1. Récupérer toutes conditions actives de l'événement
2. Pour chaque condition :
   - Récupérer valeur attribut via introspection
   - Appliquer opérateur
   - Si échec : ajouter à liste erreurs
3. Retourner true si toutes passent, false sinon
```

**Opérateurs supportés :**
- Comparaison : `=`, `!=`, `>`, `>=`, `<`, `<=`
- Chaînes : `contains`
- Tableaux : `in`
- Existence : `exists`

---

### 3. EntityIntrospectionService (344 lignes)

**Fichier :** `src/Service/EntityIntrospectionService.php`
**Responsabilité :** Découverte dynamique des propriétés d'entités via Reflection

**Méthodes clés :**

```php
public function getEntityProperties(string $entityClass): array
public function getPropertyValue(object $entity, string $propertyPath): mixed
public function getNestedProperties(string $entityClass, int $depth = 2): array
```

**Utilisation :**

```php
// Découvrir toutes propriétés de User
$properties = $introspector->getEntityProperties(User::class);
// Résultat : ['id', 'email', 'firstName', 'lastName', 'highestDivingLevel', ...]

// Accès propriété imbriquée
$value = $introspector->getPropertyValue($user, 'highestDivingLevel.code');
// Résultat : "PA40"
```

**Complexité :** ⚠️ Haute (344 lignes, beaucoup de réflexion)

---

### 4. ImageUploadService

**Fichier :** `src/Service/ImageUploadService.php`
**Responsabilité :** Upload et traitement d'images

**Méthodes clés :**

```php
public function uploadImage(UploadedFile $file, string $targetDirectory): string
public function generateThumbnail(string $imagePath, int $maxWidth = 300): string
public function deleteImage(string $path): void
```

**Processus :**

```php
1. Validation fichier (MIME type, taille)
2. Génération nom unique
3. Déplacement vers target directory
4. Génération thumbnail automatique
5. Retour chemin relatif
```

**Formats supportés :** JPG, PNG, GIF, WEBP

---

### 5. PageTemplateService

**Fichier :** `src/Service/PageTemplateService.php`
**Responsabilité :** Génération automatique de templates Twig pour pages

**Méthodes clés :**

```php
public function generateTemplate(Page $page): void
public function templateExists(string $slug): bool
```

**Template généré :**

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ page.metaTitle }}{% endblock %}
{% block meta_description %}{{ page.metaDescription }}{% endblock %}

{% block body %}
    <h1>{{ page.title }}</h1>
    <div class="content">
        {{ page.content|raw }}
    </div>
{% endblock %}
```

**Chemin :** `templates/pages/{slug}.html.twig`

---

### 6. ContentSanitizer

**Fichier :** `src/Service/ContentSanitizer.php`
**Responsabilité :** Nettoyage HTML pour prévenir XSS

**Méthodes clés :**

```php
public function sanitize(string $html): string
```

**Utilise :** HTMLPurifier

**Configuration :**
- Balises autorisées : `p`, `a`, `strong`, `em`, `ul`, `ol`, `li`, `h1-h6`, `img`, `blockquote`
- Attributs sûrs uniquement
- Scripts supprimés
- Iframes supprimés (sauf YouTube/Vimeo si configuré)

---

### 7. ModuleManager

**Fichier :** `src/Service/ModuleManager.php`
**Responsabilité :** Gestion activation/désactivation modules

**Méthodes clés :**

```php
public function isModuleActive(string $moduleName): bool
public function getModuleConfig(string $moduleName): array
public function toggleModule(string $moduleName): void
```

**Usage :**

```php
// Dans contrôleur
if (!$this->moduleManager->isModuleActive('blog')) {
    throw new NotFoundHttpException('Blog module is disabled');
}

// Dans Twig (via extension)
{% if is_module_active('blog') %}
    <a href="{{ path('blog_index') }}">Blog</a>
{% endif %}
```

---

### 8. SiteConfigService

**Fichier :** `src/Service/SiteConfigService.php`
**Responsabilité :** Accès configuration site

**Méthodes clés :**

```php
public function get(string $key, mixed $default = null): mixed
public function set(string $key, mixed $value): void
public function has(string $key): bool
```

**Usage :**

```php
$siteName = $configService->get('site_name', 'Mon Club');
$maxUpload = $configService->get('max_upload_size', 10485760);
```

---

### 9. CacheService

**Fichier :** `src/Service/CacheService.php`
**Responsabilité :** Gestion cache (notamment blog)

**Méthodes clés :**

```php
public function get(string $key): mixed
public function set(string $key, mixed $value, int $ttl = 3600): void
public function invalidate(string $key): void
public function clear(): void
```

**Stratégie :**
- Cache articles de blog
- TTL : 1 heure
- Invalidation lors de publication/modification

---

### 10. AttributeManager

**Fichier :** `src/Service/AttributeManager.php` (supposé)
**Responsabilité :** Gestion attributs EAV

**Méthodes clés :**

```php
public function getAttribute(string $entityType, int $entityId, string $attributeName): mixed
public function setAttribute(string $entityType, int $entityId, string $attributeName, mixed $value): void
public function getAttributes(string $entityType, int $entityId): array
```

---

### 11. MonitoringService

**Fichier :** `src/Service/MonitoringService.php` (supposé)
**Responsabilité :** Monitoring applicatif

**Méthodes :**
- Health checks
- Métriques
- Logs

---

### 12. ArticleValidator

**Fichier :** `src/Service/ArticleValidator.php` (supposé)
**Responsabilité :** Validation règles métier articles

---

### 13. PageContentRenderer

**Fichier :** `src/Service/PageContentRenderer.php` (supposé)
**Responsabilité :** Rendu contenu pages dynamiques

---

## 🎯 Services Manquants (À Créer)

### Services Recommandés

1. **UserApprovalService**
   - `approve(User $user): void`
   - `reject(User $user, string $reason): void`
   - `sendApprovalEmail(User $user): void`
   - Centralise logique approbation

2. **ParticipationManager**
   - `register(User $user, Event $event): EventParticipation`
   - `unregister(EventParticipation $participation): void`
   - `promoteFromWaitingList(Event $event): void`
   - Gère inscriptions et listes d'attente

3. **EventEligibilityChecker**
   - `canRegister(User $user, Event $event): bool`
   - `getIneligibilityReasons(User $user, Event $event): array`
   - Centralise vérification éligibilité

4. **ImageReorderService**
   - `reorder(Gallery $gallery, array $imageIds): void`
   - Gère réorganisation images

5. **ParticipantExporter**
   - `exportToPDF(Event $event): string`
   - `exportToCSV(Event $event): string`
   - `exportToExcel(Event $event): string`
   - Export listes participants

6. **NotificationService**
   - `sendEventConfirmation(EventParticipation $p): void`
   - `sendWaitingListNotification(User $user, Event $event): void`
   - `sendPromotionNotification(User $user, Event $event): void`
   - Centralise envois emails

7. **StatisticsService**
   - `getUserStats(User $user): array`
   - `getEventStats(Event $event): array`
   - `getClubStats(): array`
   - Statistiques et métriques

---

## 📊 Analyse des Services Actuels

### Forces

✅ **Bonne séparation des responsabilités**
- Logique métier hors des contrôleurs
- Services réutilisables

✅ **Services bien nommés**
- Noms explicites
- Responsabilité claire

✅ **Injection de dépendances**
- Utilisation Symfony DI
- Testabilité

### Faiblesses

⚠️ **Certains services trop complexes**
- `EntityIntrospectionService` : 344 lignes
- `RecurringEventService` : 254 lignes

⚠️ **Services manquants**
- Beaucoup de logique encore dans contrôleurs
- Pas de service pour notifications
- Pas de service pour export

⚠️ **Pas de tests**
- Services critiques non testés
- Risque de régression

---

## 🔄 Refactorings Recommandés

### 1. RecurringEventService

**Actuel :** Méthode géante avec switch/case

**Proposé :** Pattern Strategy

```php
interface RecurrencePatternInterface {
    public function generateDates(Event $event): array;
}

class DailyRecurrence implements RecurrencePatternInterface { }
class WeeklyRecurrence implements RecurrencePatternInterface { }
class MonthlyRecurrence implements RecurrencePatternInterface { }

class RecurringEventService {
    public function __construct(
        private array $patterns // [daily => DailyRecurrence, ...]
    ) {}

    public function generate(Event $event): array {
        $pattern = $this->patterns[$event->getRecurrenceType()];
        $dates = $pattern->generateDates($event);
        return $this->createEvents($event, $dates);
    }
}
```

### 2. Extraction de ParticipationManager

**Actuellement dans :** EventRegistrationController

**Extraire vers :**

```php
class ParticipationManager {
    public function register(User $user, Event $event, string $meetingPoint): EventParticipation
    {
        // Vérif capacité
        $status = $event->isFull() ? 'waiting_list' : 'confirmed';

        $participation = new EventParticipation();
        $participation->setEvent($event);
        $participation->setParticipant($user);
        $participation->setStatus($status);
        $participation->setMeetingPoint($meetingPoint);

        $this->em->persist($participation);
        $this->em->flush();

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

    private function promoteFromWaitingList(Event $event): void
    {
        $waitingList = $event->getWaitingListParticipations();

        if (!$event->isFull() && count($waitingList) > 0) {
            $first = $waitingList->first();
            $first->setStatus('confirmed');
            $first->setConfirmationDate(new \DateTime());
            $this->em->flush();

            // Notification
            $this->notificationService->sendPromotionNotification($first);
        }
    }
}
```

---

[➡️ Suite : Interface Utilisateur](07-interface-utilisateur.md)
