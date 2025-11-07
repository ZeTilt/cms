# Modèle de Données

[⬅️ Retour à l'index](README.md) | [⬅️ Fonctionnalités](03-fonctionnalites.md) | [➡️ Contrôleurs](05-controleurs-routes.md)

## 📊 Vue d'Ensemble du Schéma

L'application utilise **14 entités principales** organisées autour de 4 domaines :

1. **Gestion des Utilisateurs** : User, DivingLevel
2. **Gestion des Événements** : Event, EventType, EventParticipation, EventCondition
3. **Système de Contenu** : Article, Page, Gallery, Image
4. **Système Extensible** : AttributeDefinition, EntityAttribute, Module, SiteConfig

## 🗺️ Diagramme de Relations

```
                                    ┌──────────────┐
                                    │  DivingLevel │
                                    └──────┬───────┘
                                           │
                                           │ ManyToOne
                                           ▼
┌──────────────┐                     ┌────────┐
│  EventType   │◄────────┐           │  User  │
└──────┬───────┘         │           └───┬──┬─┘
       │                 │ ManyToOne     │  │
       │ ManyToOne       │               │  │ OneToMany
       ▼                 │               │  ▼
┌──────────────────┐     │               │  ┌─────────────┐
│      Event       │─────┘               │  │   Gallery   │
│ (Parent/Fils)    │                     │  └─────┬───────┘
└────┬──────┬──────┘                     │        │ OneToMany
     │      │                            │        ▼
     │      │ OneToMany                  │  ┌─────────────┐
     │      │                            │  │    Image    │
     │      ▼                            │  └─────────────┘
     │  ┌──────────────────┐            │
     │  │ EventParticipation│◄───────────┘ ManyToOne
     │  └──────────────────┘
     │
     │ OneToMany
     ▼
┌──────────────────┐
│ EventCondition   │
└──────────────────┘

┌──────────────────┐     ┌──────────────────┐
│     Article      │     │       Page       │
└──────────────────┘     └──────────────────┘

┌──────────────────┐     ┌──────────────────┐
│AttributeDefinition│    │  EntityAttribute │
└──────────────────┘     └──────────────────┘

┌──────────────────┐     ┌──────────────────┐
│     Module       │     │   SiteConfig     │
└──────────────────┘     └──────────────────┘
```

## 📦 Entités Détaillées

### 1. User (277 lignes)

**Fichier :** `src/Entity/User.php`
**Table :** `user`

**Description :** Représente un membre du club.

**Propriétés :**

| Propriété | Type | Description | Contraintes |
|-----------|------|-------------|-------------|
| `id` | int | Identifiant unique | PK, Auto-increment |
| `email` | string | Email (login) | Unique, 180 chars max |
| `roles` | array | Rôles de sécurité | JSON, default: ["ROLE_USER"] |
| `password` | string | Hash du mot de passe | 255 chars |
| `firstName` | string | Prénom | 100 chars |
| `lastName` | string | Nom | 100 chars |
| `active` | bool | Compte actif | default: false |
| `status` | string | Statut du compte | pending/approved/rejected |
| `emailVerified` | bool | Email vérifié | default: false |
| `emailVerificationToken` | string | Token de vérification | Nullable, 255 chars |
| `highestDivingLevel` | DivingLevel | Niveau de plongée le plus élevé | ManyToOne, Nullable |
| `createdAt` | DateTime | Date de création | Immutable |
| `updatedAt` | DateTime | Dernière modification | |
| `galleries` | Collection | Galeries créées | OneToMany → Gallery |

**Relations :**
- `highestDivingLevel` → `DivingLevel` (ManyToOne)
- `participations` → `EventParticipation[]` (OneToMany)
- `galleries` → `Gallery[]` (OneToMany)
- `authoredArticles` → `Article[]` (OneToMany, non mappé)

**Implémente :**
- `UserInterface` (Symfony Security)
- `PasswordAuthenticatedUserInterface`

**Méthodes importantes :**

```php
public function getFullName(): string
public function getUserIdentifier(): string  // Email
public function eraseCredentials(): void
public function hasRole(string $role): bool
```

**Index suggérés :**
- `email` (unique)
- `status`
- `active`
- `emailVerified`

---

### 2. DivingLevel (127 lignes)

**Fichier :** `src/Entity/DivingLevel.php`
**Table :** `diving_level`

**Description :** Niveaux de certification en plongée.

**Propriétés :**

| Propriété | Type | Description | Contraintes |
|-----------|------|-------------|-------------|
| `id` | int | Identifiant | PK |
| `name` | string | Nom complet | 100 chars, ex: "Plongeur Autonome 40m" |
| `code` | string | Code court | 20 chars, unique, ex: "PA40" |
| `description` | text | Description | Nullable |
| `sortOrder` | int | Ordre hiérarchique | 0 = débutant, 100 = instructeur |
| `isActive` | bool | Actif | default: true |

**Relations :**
- `users` → `User[]` (OneToMany, inverse de highestDivingLevel)

**Exemples de données :**
```sql
INSERT INTO diving_level (name, code, sort_order) VALUES
('Plongeur Encadré 12m', 'PE12', 10),
('Plongeur Autonome 20m', 'PA20', 30),
('Plongeur Autonome 40m', 'PA40', 40),
('Niveau 4 - Guide de Palanquée', 'N4', 60),
('Niveau 5 - Directeur de Plongée', 'N5', 70),
('Moniteur Fédéral 1er degré', 'MF1', 80);
```

---

### 3. Event (656 lignes)

**Fichier :** `src/Entity/Event.php`
**Table :** `event`

**Description :** Événement du club (sortie, formation, réunion, etc.). L'entité la plus complexe.

**Propriétés de base :**

| Propriété | Type | Description | Contraintes |
|-----------|------|-------------|-------------|
| `id` | int | Identifiant | PK |
| `title` | string | Titre de l'événement | 255 chars |
| `description` | text | Description complète | Nullable, HTML |
| `startDate` | DateTime | Date/heure début | |
| `endDate` | DateTime | Date/heure fin | |
| `location` | string | Lieu | 255 chars, nullable |
| `status` | string | Statut | draft/published/cancelled |
| `maxParticipants` | int | Capacité max | Nullable (illimité si null) |

**Propriétés spécifiques plongée :**

| Propriété | Type | Description |
|-----------|------|-------------|
| `minDivingLevel` | DivingLevel | Niveau minimum requis | ManyToOne, Nullable |
| `clubMeetingTime` | DateTime | Heure RDV au club | Nullable |
| `siteMeetingTime` | DateTime | Heure RDV sur site | Nullable |

**Propriétés de récurrence :**

| Propriété | Type | Description | Valeurs possibles |
|-----------|------|-------------|-------------------|
| `isRecurring` | bool | Est récurrent | default: false |
| `recurrenceType` | string | Type de récurrence | daily/weekly/monthly |
| `recurrenceInterval` | int | Intervalle | Ex: tous les 2 jours |
| `recurrenceWeekdays` | array | Jours de la semaine | JSON: [1,3,5] = Lun,Mer,Ven |
| `recurrenceEndDate` | Date | Date de fin génération | Nullable |
| `parentEvent` | Event | Événement parent | ManyToOne, Self-référence |
| `generatedEvents` | Collection | Événements générés | OneToMany, Self-référence |

**Relations :**

| Relation | Type | Cible | Description |
|----------|------|-------|-------------|
| `eventType` | ManyToOne | EventType | Type d'événement |
| `contactPerson` | ManyToOne | User | Personne de contact |
| `minDivingLevel` | ManyToOne | DivingLevel | Niveau requis |
| `conditions` | OneToMany | EventCondition | Conditions d'éligibilité |
| `participations` | OneToMany | EventParticipation | Inscriptions |
| `parentEvent` | ManyToOne | Event | Événement parent (si récurrent) |
| `generatedEvents` | OneToMany | Event | Événements fils (si parent) |

**Méthodes importantes :**

```php
public function getAvailablePlaces(): ?int
public function isFull(): bool
public function canUserRegister(User $user): bool
public function hasWaitingList(): bool
public function getConfirmedParticipations(): Collection
public function getWaitingListParticipations(): Collection
```

**Index suggérés :**
- `startDate`, `endDate`
- `status`
- `eventType.id`
- `isRecurring`
- `parentEvent.id`

---

### 4. EventType (149 lignes)

**Fichier :** `src/Entity/EventType.php`
**Table :** `event_type`

**Description :** Types/catégories d'événements.

**Propriétés :**

| Propriété | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `id` | int | Identifiant | PK |
| `name` | string | Nom du type | "Sortie Plongée" |
| `code` | string | Code unique | "sortie" |
| `color` | string | Couleur (hex) | "#3B82F6" |
| `description` | text | Description | Nullable |
| `isActive` | bool | Actif | default: true |

**Relations :**
- `events` → `Event[]` (OneToMany)

**Exemples :**
```
sortie      | Sortie Plongée       | #3B82F6 (bleu)
formation   | Formation            | #10B981 (vert)
technique   | Séance Technique     | #F59E0B (orange)
reunion     | Réunion              | #6B7280 (gris)
ag          | Assemblée Générale   | #EF4444 (rouge)
```

---

### 5. EventParticipation (166 lignes)

**Fichier :** `src/Entity/EventParticipation.php`
**Table :** `event_participation`

**Description :** Inscription d'un utilisateur à un événement.

**Propriétés :**

| Propriété | Type | Description | Valeurs |
|-----------|------|-------------|---------|
| `id` | int | Identifiant | PK |
| `event` | Event | Événement | ManyToOne |
| `participant` | User | Participant | ManyToOne |
| `status` | string | Statut inscription | confirmed/waiting_list/cancelled |
| `registrationDate` | DateTime | Date d'inscription | |
| `confirmationDate` | DateTime | Date de confirmation | Nullable |
| `notes` | text | Notes (DP) | Nullable |
| `meetingPoint` | string | Point de RDV choisi | club/site |
| `isWaitingList` | bool | En liste d'attente | Calculé depuis status |

**Relations :**
- `event` → `Event` (ManyToOne)
- `participant` → `User` (ManyToOne)

**Contraintes :**
- Un user ne peut s'inscrire qu'une fois par événement
- Index composite : `(event_id, participant_id)` UNIQUE

**Méthodes :**

```php
public function confirm(): void  // Passe de waiting_list à confirmed
public function cancel(): void
public function isConfirmed(): bool
public function isWaitingList(): bool
```

---

### 6. EventCondition (271 lignes)

**Fichier :** `src/Entity/EventCondition.php`
**Table :** `event_condition`

**Description :** Condition d'éligibilité dynamique pour un événement.

**Propriétés :**

| Propriété | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `id` | int | Identifiant | PK |
| `event` | Event | Événement | ManyToOne |
| `entityClass` | string | Classe d'entité | "App\Entity\User" |
| `attributeName` | string | Nom de l'attribut | "highestDivingLevel.code" |
| `operator` | string | Opérateur | =, !=, >, >=, <, <=, contains, in, exists |
| `value` | string | Valeur à comparer | "PA40" |
| `errorMessage` | string | Message d'erreur | "Niveau PA40 minimum requis" |
| `isActive` | bool | Condition active | default: true |

**Relations :**
- `event` → `Event` (ManyToOne)

**Exemples de conditions :**

```php
// Condition 1: Niveau minimum
entityClass: "App\Entity\User"
attributeName: "highestDivingLevel.sortOrder"
operator: ">="
value: "40"
errorMessage: "Vous devez être au minimum PA40"

// Condition 2: Certificat médical valide
entityClass: "App\Entity\User"
attributeName: "medicalCertificateExpiry"
operator: ">"
value: "TODAY"
errorMessage: "Certificat médical expiré"

// Condition 3: Assurance active
entityClass: "App\Entity\User"
attributeName: "insuranceStatus"
operator: "="
value: "active"
errorMessage: "Assurance non valide"
```

**Méthode d'évaluation :**

```php
public function evaluate(mixed $actualValue): bool
{
    return match($this->operator) {
        '=' => $actualValue == $this->value,
        '!=' => $actualValue != $this->value,
        '>' => $actualValue > $this->value,
        '>=' => $actualValue >= $this->value,
        '<' => $actualValue < $this->value,
        '<=' => $actualValue <= $this->value,
        'contains' => str_contains((string)$actualValue, $this->value),
        'in' => in_array($actualValue, json_decode($this->value, true)),
        'exists' => $actualValue !== null,
        default => false
    };
}
```

---

### 7. Article (281 lignes)

**Fichier :** `src/Entity/Article.php`
**Table :** `article`

**Description :** Article de blog.

**Propriétés :**

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int | Identifiant |
| `title` | string | Titre | 255 chars |
| `slug` | string | URL slug | Unique, 255 chars |
| `content` | text | Contenu complet | HTML |
| `excerpt` | text | Extrait/résumé | Nullable, auto-généré si vide |
| `featuredImage` | string | URL image à la une | Nullable |
| `category` | string | Catégorie | Ex: "Sorties", "Technique" |
| `tags` | array | Tags | JSON, ex: ["épave", "profond"] |
| `status` | string | Statut | draft/published/archived |
| `author` | User | Auteur | ManyToOne |
| `publishedAt` | DateTime | Date de publication | Nullable |
| `viewCount` | int | Nombre de vues | default: 0 |
| `createdAt` | DateTime | Date de création | |
| `updatedAt` | DateTime | Dernière modif | |

**Relations :**
- `author` → `User` (ManyToOne)

**Index :**
- `slug` (unique)
- `status`
- `publishedAt`
- `category`

**Sanitization :**
Le contenu HTML est nettoyé via `ContentSanitizer` (HTMLPurifier).

---

### 8. Page (292 lignes)

**Fichier :** `src/Entity/Page.php`
**Table :** `page`

**Description :** Page statique du CMS.

**Propriétés :**

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int | Identifiant |
| `title` | string | Titre | 255 chars |
| `slug` | string | URL slug | Unique |
| `content` | text | Contenu | HTML |
| `templatePath` | string | Chemin template custom | Nullable, ex: "pages/custom.html.twig" |
| `type` | string | Type de page | standard/landing/legal |
| `status` | string | Statut | draft/published/archived |
| `metaTitle` | string | Titre SEO | 255 chars, nullable |
| `metaDescription` | text | Description SEO | Nullable |
| `sortOrder` | int | Ordre affichage menu | default: 0 |
| `createdAt` | DateTime | Date création | |
| `updatedAt` | DateTime | Dernière modif | |

**Index :**
- `slug` (unique)
- `status`
- `sortOrder`

**Template auto-généré :**

À la création, `PageTemplateService` génère automatiquement un template Twig dans `templates/pages/{slug}.html.twig` si non existant.

---

### 9. Gallery (264 lignes)

**Fichier :** `src/Entity/Gallery.php`
**Table :** `gallery`

**Description :** Galerie de photos.

**Propriétés :**

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int | Identifiant |
| `title` | string | Titre | 255 chars |
| `slug` | string | URL slug | Unique |
| `description` | text | Description | Nullable |
| `coverImage` | string | Image de couverture | Nullable, URL |
| `visibility` | string | Visibilité | public/private |
| `accessCode` | string | Code d'accès | Nullable, pour galeries privées |
| `author` | User | Créateur | ManyToOne |
| `metadata` | array | Métadonnées | JSON |
| `createdAt` | DateTime | Date création | |
| `updatedAt` | DateTime | Dernière modif | |

**Relations :**
- `author` → `User` (ManyToOne)
- `images` → `Image[]` (OneToMany, cascade all, orphanRemoval)

**Sécurité :**
⚠️ **Problème :** `accessCode` stocké en clair (devrait être hashé).

---

### 10. Image (309 lignes)

**Fichier :** `src/Entity/Image.php`
**Table :** `image`

**Description :** Image d'une galerie.

**Propriétés :**

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int | Identifiant |
| `gallery` | Gallery | Galerie parente | ManyToOne |
| `filename` | string | Nom du fichier | 255 chars |
| `url` | string | URL complète | 500 chars |
| `thumbnailUrl` | string | URL thumbnail | 500 chars |
| `caption` | text | Légende | Nullable |
| `position` | int | Position ordre | default: 0 |
| `width` | int | Largeur pixels | Nullable |
| `height` | int | Hauteur pixels | Nullable |
| `uploadedAt` | DateTime | Date upload | |

**Relations :**
- `gallery` → `Gallery` (ManyToOne)

**Cascade :**
Supprimée automatiquement si la galerie est supprimée (orphanRemoval).

---

### 11. AttributeDefinition (145 lignes)

**Fichier :** `src/Entity/AttributeDefinition.php`
**Table :** `attribute_definition`

**Description :** Définit les attributs personnalisés possibles pour les entités.

**Propriétés :**

| Propriété | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `id` | int | Identifiant | |
| `name` | string | Nom technique | "medical_certificate_date" |
| `label` | string | Libellé affiché | "Date certificat médical" |
| `entityType` | string | Type d'entité | "User", "Event" |
| `fieldType` | string | Type de champ | text/date/number/boolean/select |
| `options` | array | Options (pour select) | JSON: ["Option 1", "Option 2"] |
| `required` | bool | Champ requis | default: false |
| `active` | bool | Actif | default: true |

**Exemple :**

```php
name: "medical_certificate_expiry"
label: "Date d'expiration du certificat médical"
entityType: "User"
fieldType: "date"
required: true
active: true
```

---

### 12. EntityAttribute (127 lignes)

**Fichier :** `src/Entity/EntityAttribute.php`
**Table :** `entity_attribute`

**Description :** Stocke les valeurs des attributs personnalisés (pattern EAV).

**Propriétés :**

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int | Identifiant |
| `entityType` | string | Type d'entité | "User", "Event" |
| `entityId` | int | ID de l'entité | 42 |
| `attributeName` | string | Nom de l'attribut | "licence_number" |
| `attributeValue` | text | Valeur | "F123456" (toujours stocké en string) |
| `attributeType` | string | Type pour casting | string/int/date/bool |

**Index :**
- Composite : `(entityType, entityId, attributeName)` UNIQUE
- `entityType`
- `entityId`

**Exemple de données :**

```sql
INSERT INTO entity_attribute VALUES
(1, 'User', 42, 'licence_number', 'F123456', 'string'),
(2, 'User', 42, 'medical_cert_expiry', '2025-12-31', 'date'),
(3, 'User', 42, 'insurance_active', 'true', 'bool');
```

**Avantages EAV :**
- Flexibilité : ajout d'attributs sans migration
- Extensibilité sans modification schéma

**Inconvénients EAV :**
- Requêtes plus complexes
- Pas de contraintes DB natives
- Performance moindre sur gros volumes

---

### 13. Module (127 lignes)

**Fichier :** `src/Entity/Module.php`
**Table :** `module`

**Description :** Modules activables/désactivables de l'application.

**Propriétés :**

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int | Identifiant |
| `name` | string | Nom technique | Unique, ex: "blog" |
| `displayName` | string | Nom affiché | "Système de Blog" |
| `description` | text | Description | Nullable |
| `active` | bool | Module actif | default: true |
| `config` | array | Configuration JSON | Ex: {"posts_per_page": 10} |

**Modules système :**
```
events   | Gestion des Événements | true
blog     | Système de Blog        | true
pages    | Pages Statiques        | true
gallery  | Galeries Photos        | true
```

---

### 14. SiteConfig (61 lignes)

**Fichier :** `src/Entity/SiteConfig.php`
**Table :** `site_config`

**Description :** Configuration site (key-value store).

**Propriétés :**

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int | Identifiant |
| `configKey` | string | Clé | Unique, 100 chars |
| `configValue` | text | Valeur | |
| `description` | text | Description | Nullable |

**Index :**
- `configKey` (unique)

**Exemples :**
```sql
INSERT INTO site_config VALUES
(1, 'site_name', 'Club Subaquatique des Vénètes', 'Nom du site'),
(2, 'contact_email', 'contact@venetes.fr', 'Email de contact'),
(3, 'max_upload_size', '10485760', 'Taille max upload (bytes)'),
(4, 'events_close_hours_before', '24', 'Fermeture inscriptions (heures)');
```

---

## 🔗 Relations Clés

### User ↔ Event (via EventParticipation)

```
User (1) ──────< (N) EventParticipation (N) >────── (1) Event
```

Un user peut participer à plusieurs événements, un événement peut avoir plusieurs participants.

### Event ↔ Event (Récurrence)

```
Event Parent (1) ──────< (N) Event Fils
```

Auto-référence : un événement récurrent génère des événements fils.

### Gallery ↔ Image

```
Gallery (1) ──────< (N) Image
```

Cascade ALL, orphanRemoval : suppression galerie → suppression images.

### AttributeDefinition → EntityAttribute

```
AttributeDefinition (définit le schéma)
          ↓ (référence implicite par name)
EntityAttribute (stocke les valeurs)
```

Pas de foreign key directe, lien par convention de nommage.

---

## 📋 Migrations

**Fichiers :** `migrations/`

### Version20250919060820

**Changements principaux :**
- Ajout de `clubMeetingTime`, `siteMeetingTime` à Event
- Ajout de `minDivingLevel` (relation)
- Ajout de `isWaitingList` à EventParticipation
- Création table `diving_level`

### Version20250919064527

(Contenu non examiné, mais existe)

**Comment créer une migration :**

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## 🔍 Requêtes Fréquentes

### Trouver les prochains événements

```php
$events = $eventRepository->createQueryBuilder('e')
    ->where('e.startDate > :now')
    ->andWhere('e.status = :status')
    ->setParameter('now', new \DateTime())
    ->setParameter('status', 'published')
    ->orderBy('e.startDate', 'ASC')
    ->getQuery()
    ->getResult();
```

### Participants confirmés d'un événement

```php
$participants = $event->getParticipations()
    ->filter(fn($p) => $p->getStatus() === 'confirmed');
```

### Utilisateurs par niveau

```php
$users = $userRepository->createQueryBuilder('u')
    ->join('u.highestDivingLevel', 'dl')
    ->where('dl.code = :code')
    ->setParameter('code', 'PA40')
    ->getQuery()
    ->getResult();
```

### Articles publiés récents

```php
$articles = $articleRepository->createQueryBuilder('a')
    ->where('a.status = :status')
    ->andWhere('a.publishedAt IS NOT NULL')
    ->andWhere('a.publishedAt <= :now')
    ->setParameter('status', 'published')
    ->setParameter('now', new \DateTime())
    ->orderBy('a.publishedAt', 'DESC')
    ->setMaxResults(10)
    ->getQuery()
    ->getResult();
```

---

[➡️ Suite : Contrôleurs et Routes](05-controleurs-routes.md)
