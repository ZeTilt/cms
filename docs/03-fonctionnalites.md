# Fonctionnalités Détaillées

[⬅️ Retour à l'index](README.md) | [⬅️ Architecture](02-architecture-technique.md) | [➡️ Modèle de Données](04-modele-donnees.md)

## 📅 Module Événements & Calendrier

### 1.1 Gestion des Événements

#### Création d'Événements

**Contrôleur :** `AdminEventController::new()` (src/Controller/Admin/AdminEventController.php:52)
**Route :** `/admin/events/new`
**Accès :** ROLE_ADMIN

**Champs disponibles :**
- **Titre** : Nom de l'événement
- **Description** : Détails (support HTML via éditeur)
- **Type d'événement** : Sélection du type (sortie, formation, AG, etc.)
- **Dates** :
  - Date de début (date + heure)
  - Date de fin (date + heure)
- **Lieu** : Localisation de l'événement
- **Contact** : Personne de contact (sélection parmi les utilisateurs)
- **Capacité** :
  - Nombre maximum de participants
  - Si 0 ou null = illimité
- **Niveau plongée** :
  - Niveau minimum requis
  - Heure RDV club
  - Heure RDV sur site

**Récurrence (optionnel) :**
- ☑️ Événement récurrent
- **Type** : Quotidien / Hebdomadaire / Mensuel
- **Intervalle** : Tous les X jours/semaines/mois
- **Jours de la semaine** : (pour récurrence hebdomadaire)
  - ☑️ Lundi, ☑️ Mardi, etc.
- **Date de fin** : Jusqu'à quand générer les occurrences

#### Édition d'Événements

**Route :** `/admin/events/{id}/edit`

**Cas particulier - Événements récurrents :**
1. Si événement parent → modifications propagées aux futurs événements
2. Si événement fils → modification uniquement de cette occurrence
3. Option "Supprimer à partir de cette date" pour les séries

#### Suppression d'Événements

**Route :** `/admin/events/{id}/delete`

**Gestion cascade :**
- Suppression d'un événement parent → supprime tous les fils futurs
- Suppression d'un événement fils → n'affecte pas les autres
- Confirmation obligatoire si participants inscrits

### 1.2 Types d'Événements

**Contrôleur :** `AdminEventTypeController` (src/Controller/Admin/AdminEventTypeController.php)
**Route :** `/admin/event-types`
**Accès :** ROLE_ADMIN

**Fonctionnalités :**
- Créer des types personnalisés
- Définir une couleur (affichage calendrier)
- Définir un code unique
- Activer/désactiver

**Types par défaut suggérés :**
```
Code          | Nom                  | Couleur
--------------+----------------------+---------
sortie        | Sortie Plongée       | Bleu
formation     | Formation            | Vert
technique     | Séance Technique     | Orange
reunion       | Réunion              | Gris
ag            | Assemblée Générale   | Rouge
convivialite  | Événement Convivial  | Violet
```

### 1.3 Calendrier Public

**Contrôleur :** `CalendarController` (src/Controller/CalendarController.php)
**Route :** `/calendrier`
**Accès :** Public

**Fonctionnalités :**
- Affichage mensuel
- Navigation mois par mois
- Filtrage par type d'événement
- Code couleur selon le type
- Vue détaillée au clic

**Vue détaillée d'un événement :**
**Route :** `/calendrier/evenement/{id}`

**Informations affichées :**
- Titre, description
- Date et heure
- Lieu
- Type d'événement
- Places disponibles / Total
- Niveau minimum requis
- Bouton "S'inscrire" (si connecté et éligible)
- Liste des participants (si admin/DP)

### 1.4 Inscription aux Événements

**Contrôleur :** `EventRegistrationController` (src/Controller/EventRegistrationController.php)
**Route :** `/events/{id}/register`
**Accès :** ROLE_USER

**Processus d'inscription :**

```
1. User clique "S'inscrire"
      │
      ├─► Vérifie si user déjà inscrit
      │   └─► Si oui → Message d'erreur
      │
      ├─► Évalue les conditions d'éligibilité
      │   ├─► Niveau de plongée suffisant ?
      │   ├─► Attributs personnalisés OK ?
      │   └─► Si non → Message d'erreur avec raison
      │
      ├─► Vérifie la capacité
      │   ├─► Places disponibles → Statut "confirmed"
      │   └─► Complet → Statut "waiting_list"
      │
      ├─► Choix du point de rendez-vous
      │   ├─► RDV au club
      │   └─► RDV sur site
      │
      ├─► Crée EventParticipation
      │
      └─► Confirmation
```

**Statuts de participation :**
- `confirmed` : Inscription confirmée
- `waiting_list` : En liste d'attente
- `cancelled` : Annulée par le participant

### 1.5 Désinscription

**Route :** `/events/{id}/unregister`
**Accès :** ROLE_USER (propriétaire de l'inscription)

**Processus :**
1. Suppression de l'inscription
2. Si liste d'attente non vide → Promotion automatique du premier
3. (Notification email si configuré)

### 1.6 Système de Conditions d'Éligibilité

**Contrôleur :** `AdminEventConditionController` (src/Controller/Admin/AdminEventConditionController.php)
**Route :** `/admin/events/{eventId}/conditions`
**Service :** `EventConditionService` (src/Service/EventConditionService.php)

**Principe :** Définir des règles dynamiques pour limiter l'accès aux événements.

**Exemple de conditions :**

```php
Condition 1:
  Attribut: highestDivingLevel.code
  Opérateur: >=
  Valeur: PA40
  Message erreur: "Vous devez être au minimum PA40"

Condition 2:
  Attribut: medical_certificate_date
  Opérateur: exists
  Message erreur: "Certificat médical requis"

Condition 3:
  Attribut: insurance_status
  Opérateur: =
  Valeur: active
  Message erreur: "Assurance non valide"
```

**Opérateurs supportés :**
- `=` : Égal
- `!=` : Différent
- `>` : Supérieur
- `>=` : Supérieur ou égal
- `<` : Inférieur
- `<=` : Inférieur ou égal
- `contains` : Contient la chaîne
- `in` : Dans la liste (tableau)
- `exists` : L'attribut existe et n'est pas null

**Introspection d'entité :**

Le système peut explorer automatiquement les propriétés de `User` et `Event` pour construire les conditions via `EntityIntrospectionService`.

### 1.7 Gestion des Participants (Interface DP)

**Contrôleur :** `DpEventController` (src/Controller/Dp/DpEventController.php)
**Route :** `/dp/events`
**Accès :** ROLE_DP (Directeur de Plongée)

**Fonctionnalités spécifiques :**
- Vue des participants par niveau de plongée
- Validation des inscriptions
- Statistiques de l'événement
- Export de la liste (prévu)
- Notes sur les participants

**Affichage participants :**
```
Niveau PA40 (3 plongeurs)
  - Dupont Jean (RDV Club)
  - Martin Sophie (RDV Site)
  - Durand Paul (RDV Club)

Niveau PA20 (5 plongeurs)
  - ...

Niveau PE12 (2 plongeurs)
  - ...
```

## 👤 Module Gestion des Utilisateurs

### 2.1 Inscription Publique

**Contrôleur :** `RegistrationController` (src/Controller/RegistrationController.php)
**Route :** `/register`
**Accès :** Public

**Workflow d'inscription :**

```
1. Visiteur remplit formulaire
   ├─► Prénom
   ├─► Nom
   ├─► Email (unique)
   ├─► Mot de passe (2x pour confirmation)
   └─► Niveau de plongée (optionnel)

2. Validation formulaire
   ├─► Email non déjà utilisé
   ├─► Mot de passe respecte les règles
   └─► Champs requis remplis

3. Création compte
   ├─► Statut: "pending" (en attente)
   ├─► Email vérifié: false
   ├─► Token de vérification généré
   └─► Compte inactif

4. Email de vérification envoyé
   (infrastructure prête mais à compléter)

5. User clique lien dans email
   └─► emailVerified = true

6. Admin approuve/rejette
   ├─► Si approuvé: status = "approved", active = true
   └─► Si rejeté: status = "rejected"

7. User peut se connecter
```

### 2.2 Authentification

**Contrôleur :** `SecurityController` (src/Controller/SecurityController.php)
**Routes :**
- `/login` : Formulaire de connexion
- `/logout` : Déconnexion

**Sécurité :**
- Protection CSRF
- Hashing mot de passe (auto: bcrypt/argon2)
- UserChecker vérifie le statut du compte
- "Remember me" disponible

**UserChecker :** `src/Security/UserChecker.php`

Vérifie avant connexion :
- Compte actif (`active = true`)
- Statut approuvé (`status = 'approved'`)
- Email vérifié (`emailVerified = true`)

### 2.3 Gestion des Utilisateurs (Admin)

**Contrôleur :** `AdminUserController` (src/Controller/Admin/AdminUserController.php)
**Route :** `/admin/users`
**Accès :** ROLE_ADMIN

**Fonctionnalités :**

#### Liste des utilisateurs
- Filtrage par statut (pending, approved, rejected)
- Filtrage par rôle
- Recherche par nom/email

#### Approbation des comptes
**Action :** `approve()`
```php
- Change status → "approved"
- Active le compte
- (Email de bienvenue optionnel)
```

#### Rejet des comptes
**Action :** `reject()`
```php
- Change status → "rejected"
- Désactive le compte
- (Email de notification optionnel)
```

#### Édition utilisateur
**Champs modifiables :**
- Prénom, nom
- Email
- Niveau de plongée
- Rôles (ROLE_USER, ROLE_DP, ROLE_ADMIN, ROLE_SUPER_ADMIN)
- Statut actif/inactif

#### Suppression utilisateur
- Soft delete (désactivation) ou hard delete selon configuration

### 2.4 Profil Utilisateur

**Contrôleur :** `UserProfileController` (src/Controller/UserProfileController.php)
**Route :** `/profile`
**Accès :** ROLE_USER

**Informations affichées :**
- Informations personnelles
- Niveau de plongée
- Événements auxquels inscrit
- Historique de participations

**Actions disponibles :**
- Modifier informations
- Changer mot de passe
- Gérer attributs personnalisés

### 2.5 Système d'Attributs Utilisateur (EAV)

**Contrôleur :** `AdminUserAttributeController` (src/Controller/Admin/AdminUserAttributeController.php)
**Route :** `/admin/user-attributes`
**Accès :** ROLE_ADMIN

**Principe :** Ajouter des champs personnalisés sans modifier le schéma DB.

**Exemples d'attributs :**
```
Attribut                  | Type      | Requis
--------------------------+-----------+--------
licence_number            | string    | Non
medical_cert_date         | date      | Oui
medical_cert_expiry       | date      | Oui
insurance_number          | string    | Non
insurance_expiry          | date      | Non
emergency_contact_name    | string    | Oui
emergency_contact_phone   | string    | Oui
```

**Définition d'attribut :**
**Contrôleur :** `AdminAttributeDefinitionController`
**Route :** `/admin/attribute-definitions`

**Champs :**
- Nom (clé technique)
- Label (affiché à l'user)
- Type de champ (text, date, number, boolean, select)
- Options (pour select)
- Requis ou non
- Entité cible (User, Event, etc.)

## 🏊 Module Plongée

### 3.1 Niveaux de Plongée

**Contrôleur :** `AdminDivingLevelController` (src/Controller/Admin/AdminDivingLevelController.php)
**Route :** `/admin/diving-levels`
**Accès :** ROLE_ADMIN

**Gestion des certifications :**

**Champs :**
- Nom complet (ex: "Plongeur Niveau 1")
- Code (ex: "PE12", "PA20", "PA40", "PA60")
- Description
- Ordre de tri (pour classement hiérarchique)
- Actif/Inactif

**Niveaux FFESSM standards :**
```
Code   | Nom                          | Profondeur
-------+------------------------------+-----------
PE12   | Plongeur Encadré 12m         | 12m
PE20   | Plongeur Encadré 20m         | 20m
PE40   | Plongeur Encadré 40m         | 40m
PE60   | Plongeur Encadré 60m         | 60m
PA12   | Plongeur Autonome 12m        | 12m
PA20   | Plongeur Autonome 20m        | 20m
PA40   | Plongeur Autonome 40m        | 40m
PA60   | Plongeur Autonome 60m        | 60m
N4     | Niveau 4 (Guide de Palanquée)| 60m
N5     | Niveau 5 (Directeur de Plongée)| -
MF1    | Moniteur Fédéral 1er degré   | -
MF2    | Moniteur Fédéral 2ème degré  | -
```

### 3.2 Interface Directeur de Plongée (DP)

**Contrôleur :** `DpEventController` (src/Controller/Dp/DpEventController.php)
**Route :** `/dp/events`
**Template :** `templates/dp/` (src/Controller/Dp/)
**Accès :** ROLE_DP

**Vue spécialisée pour organiser les plongées :**

**Dashboard DP :**
- Liste des prochaines sorties plongée
- Événements nécessitant validation
- Statistiques rapides

**Gestion d'un événement :**
- Participants groupés par niveau
- Points de RDV choisis par chacun
- Notes sur les participants
- Validation finale

**Exemple d'affichage :**
```
Sortie Épave - Samedi 15 juin 2025
===================================

RDV Club (8h30): 7 plongeurs
RDV Site (9h30): 5 plongeurs

Répartition par niveau:
-----------------------
N4 - Guide (1):
  • Dupont Jean (RDV Club) - Note: "Peut encadrer"

PA40 (4):
  • Martin Sophie (RDV Club)
  • Durand Paul (RDV Site)
  • Bernard Alice (RDV Club)
  • Petit Marc (RDV Site)

PA20 (5):
  • ...

Palanquées suggérées:
---------------------
Palanquée 1 (Autonome 40m):
  - Dupont Jean (N4)
  - Martin Sophie (PA40)
  - Durand Paul (PA40)

Palanquée 2 (Encadrée 20m):
  - Bernard Alice (PA40) - Guide
  - Petit Marc (PA20)
  - ...
```

## 📝 Module CMS (Content Management System)

### 4.1 Pages Statiques

**Contrôleur :** `PagesController` (src/Controller/Admin/PagesController.php)
**Route Admin :** `/admin/pages`
**Route Publique :** `/{slug}`
**Accès :** Public (lecture), ROLE_ADMIN (écriture)

**Fonctionnalités :**

#### Création de page
1. Remplir formulaire :
   - Titre
   - Slug (URL)
   - Contenu (éditeur riche)
   - Meta titre (SEO)
   - Meta description (SEO)
   - Type de page (standard, landing, etc.)
   - Ordre d'affichage

2. Génération automatique du template
   **Service :** `PageTemplateService`

   Crée automatiquement :
   ```
   templates/pages/{slug}.html.twig
   ```

   Contenu généré :
   ```twig
   {% extends 'base.html.twig' %}

   {% block title %}{{ page.metaTitle }}{% endblock %}
   {% block meta_description %}{{ page.metaDescription }}{% endblock %}

   {% block body %}
       <h1>{{ page.title }}</h1>
       <div>
           {{ page.content|raw }}
       </div>
   {% endblock %}
   ```

3. Publication
   - Statut: draft → published → archived

#### Édition de page
- Modification du contenu
- Si template custom existe → utilisé à la place du généré
- Versioning (à implémenter)

#### Suppression de page
- Soft delete (archived) ou hard delete
- Template non supprimé (sécurité)

**Pages suggérées :**
- `/qui-sommes-nous` : Présentation du club
- `/ou-nous-trouver` : Localisation, horaires
- `/tarifs-2025` : Grille tarifaire
- `/nos-partenaires` : Partenaires
- `/nos-activites` : Activités proposées
- `/mentions-legales` : Mentions légales
- `/cgv` : Conditions générales

### 4.2 Blog

**Contrôleur :** `ArticleController` (src/Controller/Admin/ArticleController.php), `BlogController` (src/Controller/BlogController.php)
**Route Admin :** `/admin/articles`
**Route Publique :** `/blog`
**Accès :** Public (lecture), ROLE_ADMIN (écriture)

**Entité :** `Article` (src/Entity/Article.php:1)

**Fonctionnalités :**

#### Création d'article
**Champs :**
- Titre
- Slug (auto-généré ou manuel)
- Contenu (éditeur Quill.js)
- Extrait (auto ou manuel)
- Image à la une (upload)
- Catégorie
- Tags (multiple)
- Auteur (auto: user connecté)
- Date de publication (programmable)
- Statut (draft/published/archived)

#### Liste des articles (public)
**Route :** `/blog`

**Affichage :**
- Grid ou liste d'articles
- Image à la une
- Titre + extrait
- Auteur, date
- Catégorie
- Pagination

**Filtres :**
- Par catégorie
- Par tag
- Par auteur
- Par date

#### Vue article
**Route :** `/blog/article/{slug}`

**Affichage :**
- Titre
- Image à la une
- Auteur, date de publication
- Catégorie et tags
- Contenu complet (HTML sanitizé)
- Articles similaires (par catégorie/tags)

**Sanitization :** `ContentSanitizer` via HTMLPurifier

#### Gestion admin
- Liste avec filtres (statut, catégorie, auteur)
- Édition WYSIWYG
- Prévisualisation
- Publication programmée
- Statistiques (vues - à implémenter)

**Cache :** `CacheService` pour optimiser performances

### 4.3 Galeries Photos

**Contrôleur :**
- `GalleryController` (src/Controller/Admin/GalleryController.php) - Admin
- `PublicGalleryController` (src/Controller/PublicGalleryController.php) - Public

**Routes :**
- `/admin/galleries` - Gestion
- `/galleries` - Liste publique
- `/gallery/{slug}` - Vue galerie

**Accès :** Public ou privé avec code

**Fonctionnalités :**

#### Création galerie
**Champs :**
- Titre
- Slug
- Description
- Visibilité :
  - Public
  - Privé (avec code d'accès)
- Métadonnées (JSON libre)

#### Upload d'images
**Service :** `ImageUploadService`

**Processus :**
1. Upload multiple
2. Validation (type MIME, taille)
3. Génération thumbnail automatique
4. Stockage : `/public/uploads/galleries/{gallery_id}/`
5. Sauvegarde métadonnées :
   - Nom fichier
   - URL complète
   - URL thumbnail
   - Caption
   - Dimensions
   - Position (ordre)

#### Gestion images
- Réorganisation par drag & drop
- Édition caption
- Suppression
- Définir image de couverture

#### Vue publique
**Avec code d'accès :**
```
1. User accède /gallery/sortie-juin-2025
2. Si privé → formulaire code
3. User entre code
4. Validation
5. Session stocke l'accès
6. Affichage galerie
```

**Affichage :**
- Grid responsive
- Lightbox au clic
- Carousel
- Téléchargement (si autorisé)

#### Permissions
- Propriétaire (author) : plein accès
- Admins : plein accès
- Autres : lecture seule (si code fourni)

## ⚙️ Module Configuration

### 5.1 Gestion des Modules

**Contrôleur :** `AdminModuleController` (src/Controller/Admin/AdminModuleController.php)
**Route :** `/admin/modules`
**Accès :** ROLE_SUPER_ADMIN
**Service :** `ModuleManager` (src/Service/ModuleManager.php)

**Modules disponibles :**
```
Module    | Description                | Actif par défaut
----------+----------------------------+-----------------
events    | Gestion événements         | Oui
blog      | Système de blog            | Oui
pages     | Pages statiques            | Oui
gallery   | Galeries photos            | Oui
```

**Actions :**
- Activer/Désactiver un module
- Configurer (JSON config par module)

**Exemple config blog :**
```json
{
  "posts_per_page": 10,
  "allow_comments": false,
  "rss_feed": true
}
```

**Effets de la désactivation :**
- Routes désactivées (404)
- Liens menu cachés
- Accès contrôleur bloqué

### 5.2 Configuration Site

**Contrôleur :** `AdminConfigController` (src/Controller/Admin/AdminConfigController.php)
**Route :** `/admin/config`
**Accès :** ROLE_ADMIN
**Service :** `SiteConfigService`

**Entité :** `SiteConfig` (key-value store)

**Configurations disponibles :**
```
Clé                      | Valeur                    | Description
-------------------------+---------------------------+-------------------
site_name                | Club des Vénètes          | Nom du site
site_tagline             | Plongée à Vannes          | Slogan
contact_email            | contact@venetes.fr        | Email contact
facebook_url             | https://fb.com/...        | FB page
max_upload_size          | 10485760                  | 10MB en bytes
events_registration_days | 1                         | Délai avant event
```

**Usage dans templates :**
```twig
{{ site_config('site_name') }}
{{ site_config('contact_email') }}
```

**Usage dans services :**
```php
$siteName = $this->siteConfig->get('site_name');
```

---

[➡️ Suite : Modèle de Données](04-modele-donnees.md)
