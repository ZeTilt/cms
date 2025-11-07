# Architecture Technique

[⬅️ Retour à l'index](README.md) | [⬅️ Vue d'Ensemble](01-vue-ensemble.md) | [➡️ Fonctionnalités](03-fonctionnalites.md)

## 🏗️ Stack Technologique

### Backend

| Technologie | Version | Rôle |
|-------------|---------|------|
| **PHP** | 8.2+ | Langage principal |
| **Symfony** | 7.3 | Framework MVC |
| **Doctrine ORM** | 3.x | Mapping objet-relationnel |
| **Twig** | 3.x | Moteur de templates |
| **Symfony Security** | 7.3 | Authentification & Autorisation |
| **Symfony Forms** | 7.3 | Gestion des formulaires |
| **HTMLPurifier** | Custom | Sanitization de contenu |

### Base de Données

| Technologie | Support | Usage |
|-------------|---------|-------|
| **MySQL** | ✅ | Production recommandée |
| **SQLite** | ✅ | Développement/tests |
| **PostgreSQL** | ⚠️ | Compatible théoriquement (non testé) |

### Frontend

| Technologie | Version | Méthode | Rôle |
|-------------|---------|---------|------|
| **Tailwind CSS** | 3.x | CDN | Framework CSS utility-first |
| **JavaScript** | ES6+ | Vanilla | Interactivité |
| **Quill.js** | - | CDN | Éditeur WYSIWYG (blog) |

**Note importante :** Pas de build system frontend (pas de Webpack, Vite, etc.). Tout est chargé via CDN ou assets statiques.

### Outils de Développement

| Outil | Usage |
|-------|-------|
| **Composer** | Gestion des dépendances PHP |
| **Symfony CLI** | Développement local |
| **Doctrine Migrations** | Versioning du schéma de base de données |
| **PHPUnit** | Tests unitaires et fonctionnels |

## 📁 Structure du Projet

```
mon-site-plongee/
├── bin/                          # Scripts exécutables
│   └── console                   # Console Symfony
├── config/                       # Configuration
│   ├── packages/                 # Config des bundles
│   │   ├── doctrine.yaml
│   │   ├── security.yaml        # 🔒 Configuration sécurité
│   │   ├── twig.yaml
│   │   └── ...
│   ├── routes/                   # Routes
│   │   └── annotations.yaml
│   └── services.yaml             # Conteneur de services
├── migrations/                   # Migrations Doctrine
│   ├── Version20250919060820.php
│   └── Version20250919064527.php
├── public/                       # Point d'entrée web
│   ├── index.php                 # Front controller
│   ├── css/                      # Styles personnalisés
│   ├── js/                       # JavaScript
│   │   ├── modules.js
│   │   ├── gallery.js
│   │   ├── carousel.js
│   │   └── ...
│   ├── uploads/                  # Fichiers uploadés
│   │   ├── images/
│   │   └── galleries/
│   ├── manifest.json             # PWA manifest
│   └── sw.js                     # Service Worker
├── src/                          # Code source
│   ├── Controller/               # 🎮 Contrôleurs (28 fichiers)
│   │   ├── Admin/                # Admin controllers
│   │   ├── Dp/                   # DP (Directeur Plongée) controllers
│   │   ├── CalendarController.php
│   │   ├── HomeController.php
│   │   └── ...
│   ├── Entity/                   # 📦 Entités Doctrine (14 fichiers)
│   │   ├── User.php
│   │   ├── Event.php
│   │   ├── EventType.php
│   │   ├── EventParticipation.php
│   │   └── ...
│   ├── Form/                     # 📝 Types de formulaires
│   │   ├── EventType.php
│   │   ├── RegistrationFormType.php
│   │   └── ...
│   ├── Repository/               # 🗄️ Repositories Doctrine
│   │   ├── UserRepository.php
│   │   ├── EventRepository.php
│   │   └── ...
│   ├── Service/                  # 🔧 Services métier (14+ fichiers)
│   │   ├── RecurringEventService.php
│   │   ├── EventConditionService.php
│   │   ├── ModuleManager.php
│   │   └── ...
│   ├── Security/                 # 🔒 Sécurité
│   │   └── UserChecker.php
│   └── Kernel.php                # Kernel Symfony
├── templates/                    # 🎨 Templates Twig (60+ fichiers)
│   ├── admin/                    # Templates admin
│   │   ├── base.html.twig
│   │   ├── dashboard.html.twig
│   │   ├── event/
│   │   ├── user/
│   │   └── ...
│   ├── dp/                       # Templates DP
│   ├── calendar/
│   ├── blog/
│   ├── gallery/
│   ├── base.html.twig            # Template de base public
│   └── home/
├── var/                          # Fichiers temporaires
│   ├── cache/
│   └── log/
├── vendor/                       # Dépendances Composer
├── .env                          # Variables d'environnement
├── .env.local                    # Variables locales (gitignored)
├── composer.json                 # Dépendances PHP
└── symfony.lock                  # Lock des recettes Symfony
```

## 🏛️ Patterns Architecturaux

### 1. MVC (Model-View-Controller)

Architecture classique Symfony :

```
┌─────────────┐
│   Request   │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│   Controller    │ ─────► Valide, orchestre
│  (28 classes)   │
└─────┬─────┬─────┘
      │     │
      │     └──────► Service Layer ──► Business Logic
      │                                      │
      ▼                                      ▼
┌──────────┐                          ┌───────────┐
│   View   │ ◄─────── Data ────────── │   Model   │
│  (Twig)  │                          │ (Entities)│
└──────────┘                          └─────┬─────┘
                                            │
                                            ▼
                                      ┌──────────┐
                                      │ Database │
                                      └──────────┘
```

### 2. Service-Oriented Architecture (SOA)

La logique métier complexe est encapsulée dans des services :

**Exemple : Gestion d'événements récurrents**

```php
// Controller (léger)
class AdminEventController {
    public function create(
        RecurringEventService $recurringService
    ) {
        // Validation
        $recurringService->generateRecurringEvents($event);
        // Response
    }
}

// Service (logique complexe)
class RecurringEventService {
    public function generateRecurringEvents(Event $event) {
        // Logique complexe de génération
        // Calcul des dates
        // Création des événements fils
    }
}
```

### 3. Repository Pattern

Abstraction de l'accès aux données via Doctrine :

```php
// Repository
class EventRepository extends ServiceEntityRepository {
    public function findUpcomingEvents(): array {
        return $this->createQueryBuilder('e')
            ->where('e.startDate > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

// Usage dans le contrôleur
$events = $eventRepository->findUpcomingEvents();
```

### 4. Entity-Attribute-Value (EAV)

Système flexible pour étendre les entités sans modifier le schéma :

```
┌─────────────────┐
│ AttributeDefinition │  ──── Définit les attributs possibles
└─────────────────┘
         │
         ▼
┌─────────────────┐
│ EntityAttribute  │  ──── Stocke les valeurs
└─────────────────┘
    entityType: 'User'
    entityId: 42
    attributeName: 'licence_number'
    attributeValue: 'F123456'
```

### 5. Strategy Pattern (Conditions)

Le système de conditions utilise le pattern Strategy :

```php
class EventCondition {
    private string $operator; // =, !=, >, <, contains, in, exists
    private mixed $value;

    public function evaluate(mixed $actualValue): bool {
        return match($this->operator) {
            '=' => $actualValue == $this->value,
            '>' => $actualValue > $this->value,
            'contains' => str_contains($actualValue, $this->value),
            // ...
        };
    }
}
```

### 6. Template Method Pattern (Récurrence)

La génération d'événements récurrents utilise une structure template :

```php
abstract class RecurrenceType {
    public function generate(Event $parent): array {
        $events = [];
        $currentDate = $parent->getStartDate();

        while ($this->shouldContinue($currentDate)) {
            if ($this->matches($currentDate)) {
                $events[] = $this->createInstance($parent, $currentDate);
            }
            $currentDate = $this->getNextDate($currentDate);
        }

        return $events;
    }

    abstract protected function matches(\DateTime $date): bool;
    abstract protected function getNextDate(\DateTime $date): \DateTime;
}
```

## 🔄 Flux de Données

### Flux de Requête HTTP

```
1. index.php (Front Controller)
      │
      ▼
2. Kernel Symfony
      │
      ├──► Routing (trouve le contrôleur)
      │
      ▼
3. Firewall Sécurité
      │
      ├──► Vérifie authentification
      ├──► Vérifie autorisations
      │
      ▼
4. Controller
      │
      ├──► Valide la requête
      ├──► Appelle les services
      │        │
      │        ▼
      │    Services Métier
      │        │
      │        ├──► Utilise les repositories
      │        │         │
      │        │         ▼
      │        │    Doctrine ORM
      │        │         │
      │        │         ▼
      │        │    Base de données
      │        │
      │        ▼
      │    Retourne les données
      │
      ▼
5. Twig (Render template)
      │
      ▼
6. Response HTTP
```

### Flux d'Inscription à un Événement

```
User clique "S'inscrire"
      │
      ▼
EventRegistrationController::register()
      │
      ├──► Vérifie si user connecté (Security)
      │
      ├──► Charge Event et User (Repositories)
      │
      ├──► Vérifie les conditions (EventConditionService)
      │         │
      │         ├──► Pour chaque condition
      │         │         ├──► Récupère valeur attribut (EntityIntrospection)
      │         │         ├──► Évalue condition
      │         │         └──► Si échec → retourne erreur
      │         │
      │         └──► Toutes conditions OK
      │
      ├──► Vérifie capacité événement
      │         │
      │         ├──► Places disponibles → Statut "confirmed"
      │         └──► Complet → Statut "waiting_list"
      │
      ├──► Crée EventParticipation
      │
      ├──► Persist + Flush (Doctrine)
      │
      ├──► Flash message succès
      │
      └──► Redirect vers événement
```

### Flux de Génération d'Événements Récurrents

```
Admin crée événement récurrent
      │
      ▼
AdminEventController::create()
      │
      ├──► Valide formulaire
      │
      ├──► Persiste événement parent
      │
      ├──► Appelle RecurringEventService
      │         │
      │         ├──► Détermine type de récurrence (daily/weekly/monthly)
      │         │
      │         ├──► Calcule toutes les dates
      │         │         │
      │         │         ├──► DAILY: chaque jour
      │         │         ├──► WEEKLY: jours spécifiés (ex: Lun, Mer, Ven)
      │         │         └──► MONTHLY: même jour du mois
      │         │
      │         ├──► Pour chaque date:
      │         │         ├──► Clone événement parent
      │         │         ├──► Ajuste dates
      │         │         ├──► Lie au parent
      │         │         └──► Persist
      │         │
      │         └──► Flush batch
      │
      └──► Flash message succès
```

## 🎯 Dependency Injection

Symfony utilise l'injection de dépendances automatique :

```php
class EventRegistrationController extends AbstractController
{
    // Injection par constructeur
    public function __construct(
        private EventConditionService $conditionService,
        private EntityManagerInterface $entityManager
    ) {}

    // Ou injection par méthode (plus courant)
    public function register(
        int $id,
        EventRepository $eventRepository,
        UserInterface $user
    ): Response {
        // Services injectés automatiquement
    }
}
```

**Configuration :** `config/services.yaml`

```yaml
services:
    _defaults:
        autowire: true      # Injection automatique
        autoconfigure: true # Auto-tag services

    App\:
        resource: '../src/'
        exclude:
            - '../src/Entity/'
            - '../src/Kernel.php'
```

## 🔐 Layers de Sécurité

### 1. Couche HTTP (Firewall)

**Fichier :** `config/packages/security.yaml`

```yaml
security:
    firewalls:
        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
            logout:
                path: app_logout

    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/dp, roles: ROLE_DP }
        - { path: ^/profile, roles: ROLE_USER }
```

### 2. Couche Contrôleur (Attributes)

```php
#[IsGranted('ROLE_ADMIN')]
class AdminEventController extends AbstractController
{
    #[Route('/admin/events/new')]
    public function new(): Response { }
}
```

### 3. Couche Service (Voter/Security)

```php
// Dans un service
if (!$this->security->isGranted('ROLE_DP')) {
    throw new AccessDeniedException();
}
```

### 4. Couche Template (Twig)

```twig
{% if is_granted('ROLE_ADMIN') %}
    <a href="{{ path('admin_dashboard') }}">Admin</a>
{% endif %}
```

## 📊 Performance et Optimisation

### Optimisations Actuelles

1. **Doctrine Query Builder** : Requêtes optimisées
2. **Lazy Loading** : Chargement à la demande des relations
3. **Cache Twig** : Templates compilés en cache
4. **Opcode Cache** : PHP OPcache (recommandé en prod)

### Points d'Amélioration Possibles

1. **N+1 Queries** : Utiliser `join` avec `fetch` dans certains cas
2. **Index Database** : Ajouter index sur colonnes fréquemment recherchées
3. **HTTP Caching** : Headers Cache-Control
4. **Asset Bundling** : Webpack Encore au lieu de CDN
5. **Redis/Memcached** : Cache application pour blog, calendrier

## 🧩 Modularité

### Système de Modules

L'application utilise un système de modules activables/désactivables :

```php
// Entity Module
class Module {
    private string $name;        // 'blog', 'pages', 'gallery'
    private bool $active;
    private array $config;       // Config JSON par module
}

// Service ModuleManager
class ModuleManager {
    public function isModuleActive(string $name): bool { }
    public function getModuleConfig(string $name): array { }
}
```

**Usage dans templates :**

```twig
{% if is_module_active('blog') %}
    <a href="{{ path('blog_index') }}">Blog</a>
{% endif %}
```

## 🧪 Testing

### Structure de Tests

```
tests/
├── Unit/              # Tests unitaires (services, entities)
├── Functional/        # Tests fonctionnels (controllers)
├── Integration/       # Tests d'intégration
└── WorkingFeaturesTest.php
```

**Configuration :** `phpunit.xml.dist`

### Exemple de Test

```php
class EventServiceTest extends KernelTestCase
{
    public function testRecurringEventGeneration(): void
    {
        $service = self::getContainer()->get(RecurringEventService::class);
        $event = new Event();
        // ... configure event

        $generated = $service->generateRecurringEvents($event);

        $this->assertCount(12, $generated);
    }
}
```

## 🌐 Environnements

### Développement (`dev`)

```env
APP_ENV=dev
APP_DEBUG=true
DATABASE_URL="sqlite:///%kernel.project_dir%/var/demo.db"
```

### Production (`prod`)

```env
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=random_secret_here
DATABASE_URL="mysql://user:pass@localhost:3306/dbname"
```

### Test (`test`)

```env
APP_ENV=test
DATABASE_URL="sqlite:///:memory:"
```

## 📦 Dépendances Principales

**Fichier :** `composer.json`

```json
{
    "require": {
        "php": ">=8.2",
        "symfony/framework-bundle": "7.3.*",
        "symfony/console": "7.3.*",
        "symfony/dotenv": "7.3.*",
        "symfony/flex": "^2",
        "symfony/form": "7.3.*",
        "symfony/mailer": "7.3.*",
        "symfony/runtime": "7.3.*",
        "symfony/security-bundle": "7.3.*",
        "symfony/twig-bundle": "7.3.*",
        "symfony/validator": "7.3.*",
        "symfony/yaml": "7.3.*",
        "doctrine/doctrine-bundle": "^2.13",
        "doctrine/orm": "^3.0",
        "ezyang/htmlpurifier": "^4.17"
    },
    "require-dev": {
        "symfony/maker-bundle": "^1.0",
        "symfony/phpunit-bridge": "^7.0",
        "symfony/web-profiler-bundle": "7.3.*"
    }
}
```

## 🎨 Conventions de Code

### Namespaces

```php
App\Controller\Admin\AdminEventController
App\Entity\Event
App\Service\RecurringEventService
App\Repository\EventRepository
App\Form\EventType
```

### Nommage

- **Contrôleurs :** `{Context}{Entity}Controller` (ex: `AdminEventController`)
- **Services :** `{Purpose}Service` (ex: `RecurringEventService`)
- **Repositories :** `{Entity}Repository` (ex: `EventRepository`)
- **Forms :** `{Entity}Type` (ex: `EventType`)
- **Entities :** Singulier, PascalCase (ex: `Event`, `EventType`)

### Routes

```php
#[Route('/admin/events/{id}/edit', name: 'admin_event_edit')]
```

Convention : `{context}_{entity}_{action}`

---

[➡️ Suite : Fonctionnalités Détaillées](03-fonctionnalites.md)
