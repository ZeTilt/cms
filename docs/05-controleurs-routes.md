# Contrôleurs et Routes

[⬅️ Retour à l'index](README.md) | [⬅️ Modèle de Données](04-modele-donnees.md) | [➡️ Services](06-services.md)

## 📍 Vue d'Ensemble

L'application compte **28 contrôleurs** organisés en 4 espaces :

1. **Public** : Routes accessibles à tous (11 contrôleurs)
2. **User** : Routes nécessitant authentification (3 contrôleurs)
3. **DP** : Interface Directeur de Plongée (2 contrôleurs)
4. **Admin** : Interface d'administration (12 contrôleurs)

**Total estimé :** **100+ routes**

## 🌐 Routes Publiques

### HomeController

**Fichier :** `src/Controller/HomeController.php`
**Préfixe :** `/`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/` | GET | `index()` | Page d'accueil |

---

### SecurityController

**Fichier :** `src/Controller/SecurityController.php`
**Préfixe :** `/`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/login` | GET/POST | `login()` | Formulaire de connexion |
| `/logout` | GET | `logout()` | Déconnexion |

---

### RegistrationController

**Fichier :** `src/Controller/RegistrationController.php`
**Préfixe :** `/`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/register` | GET/POST | `register()` | Inscription nouveau membre |
| `/verify-email` | GET | `verifyEmail()` | Vérification email |

---

### CalendarController

**Fichier :** `src/Controller/CalendarController.php`
**Préfixe :** `/calendrier`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/calendrier` | GET | `index()` | Vue calendrier mensuel |
| `/calendrier/evenement/{id}` | GET | `show()` | Détails événement |

---

### BlogController

**Fichier :** `src/Controller/BlogController.php`
**Préfixe :** `/blog`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/blog` | GET | `index()` | Liste des articles |
| `/blog/article/{slug}` | GET | `show()` | Article complet |
| `/blog/category/{category}` | GET | `category()` | Articles par catégorie |
| `/blog/tag/{tag}` | GET | `tag()` | Articles par tag |

---

### PublicGalleryController

**Fichier :** `src/Controller/PublicGalleryController.php`
**Préfixe :** `/galleries`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/galleries` | GET | `index()` | Liste galeries |
| `/gallery/{slug}` | GET | `show()` | Vue galerie |
| `/gallery/{slug}/unlock` | POST | `unlock()` | Déverrouiller avec code |

---

### PublicPagesController

**Fichier :** `src/Controller/PublicPagesController.php`
**Préfixe :** `/`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/{slug}` | GET | `show()` | Page dynamique |

**Exemples :**
- `/qui-sommes-nous`
- `/ou-nous-trouver`
- `/tarifs-2025`
- `/nos-partenaires`

---

## 👤 Routes Utilisateur (ROLE_USER)

### UserProfileController

**Fichier :** `src/Controller/UserProfileController.php`
**Préfixe :** `/profile`
**Accès :** ROLE_USER

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/profile` | GET | `index()` | Profil utilisateur |
| `/profile/edit` | GET/POST | `edit()` | Modifier profil |
| `/profile/password` | POST | `changePassword()` | Changer mot de passe |

---

### EventRegistrationController

**Fichier :** `src/Controller/EventRegistrationController.php`
**Préfixe :** `/events`
**Accès :** ROLE_USER

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/events/{id}/register` | POST | `register()` | S'inscrire à événement |
| `/events/{id}/unregister` | POST | `unregister()` | Se désinscrire |
| `/events/{id}/choose-meeting-point` | POST | `chooseMeetingPoint()` | Choisir point RDV |

---

## 🏊 Routes Directeur de Plongée (ROLE_DP)

### DpEventController

**Fichier :** `src/Controller/Dp/DpEventController.php` (308 lignes)
**Préfixe :** `/dp`
**Accès :** ROLE_DP

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/dp/events` | GET | `index()` | Liste événements plongée |
| `/dp/events/{id}` | GET | `show()` | Détails + participants par niveau |
| `/dp/events/{id}/validate` | POST | `validate()` | Valider inscriptions |
| `/dp/events/{id}/export` | GET | `exportParticipants()` | Exporter liste (PDF) |

**Complexité :** 308 lignes (contrôleur complexe)

---

### DpApiController

**Fichier :** `src/Controller/Dp/DpApiController.php`
**Préfixe :** `/dp/api`
**Accès :** ROLE_DP

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/dp/api/events/{id}/participants` | GET | `getParticipants()` | JSON participants |

**Note :** API minimale, à développer.

---

## ⚙️ Routes Admin (ROLE_ADMIN)

### AdminController

**Fichier :** `src/Controller/Admin/AdminController.php`
**Préfixe :** `/admin`
**Accès :** ROLE_ADMIN

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin` | GET | `dashboard()` | Dashboard principal |

---

### AdminEventController

**Fichier :** `src/Controller/Admin/AdminEventController.php` (282 lignes)
**Préfixe :** `/admin/events`
**Accès :** ROLE_ADMIN

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/events` | GET | `index()` | Liste événements |
| `/admin/events/new` | GET/POST | `new()` | Créer événement |
| `/admin/events/{id}` | GET | `show()` | Détails événement |
| `/admin/events/{id}/edit` | GET/POST | `edit()` | Modifier événement |
| `/admin/events/{id}/delete` | POST | `delete()` | Supprimer événement |
| `/admin/events/{id}/delete-from-date` | POST | `deleteFromDate()` | Supprimer série récurrente |

**Services utilisés :**
- `RecurringEventService` pour génération récurrence

---

### AdminEventTypeController

**Fichier :** `src/Controller/Admin/AdminEventTypeController.php` (203 lignes)
**Préfixe :** `/admin/event-types`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/event-types` | GET | `index()` | Liste types |
| `/admin/event-types/new` | GET/POST | `new()` | Créer type |
| `/admin/event-types/{id}/edit` | GET/POST | `edit()` | Modifier type |
| `/admin/event-types/{id}/delete` | POST | `delete()` | Supprimer type |

---

### AdminEventConditionController

**Fichier :** `src/Controller/Admin/AdminEventConditionController.php` (296 lignes)
**Préfixe :** `/admin/events/{eventId}/conditions`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/events/{eventId}/conditions` | GET | `index()` | Liste conditions événement |
| `/admin/events/{eventId}/conditions/new` | GET/POST | `new()` | Ajouter condition |
| `/admin/events/{eventId}/conditions/{id}/edit` | GET/POST | `edit()` | Modifier condition |
| `/admin/events/{eventId}/conditions/{id}/delete` | POST | `delete()` | Supprimer condition |

**Services utilisés :**
- `EntityIntrospectionService` pour découvrir attributs disponibles

**Complexité :** 296 lignes (logique complexe d'introspection)

---

### AdminUserController

**Fichier :** `src/Controller/Admin/AdminUserController.php` (280 lignes)
**Préfixe :** `/admin/users`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/users` | GET | `index()` | Liste utilisateurs |
| `/admin/users/{id}` | GET | `show()` | Détails utilisateur |
| `/admin/users/{id}/edit` | GET/POST | `edit()` | Modifier utilisateur |
| `/admin/users/{id}/approve` | POST | `approve()` | Approuver compte |
| `/admin/users/{id}/reject` | POST | `reject()` | Rejeter compte |
| `/admin/users/{id}/delete` | POST | `delete()` | Supprimer utilisateur |

**Complexité :** 280 lignes

---

### AdminDivingLevelController

**Fichier :** `src/Controller/Admin/AdminDivingLevelController.php` (191 lignes)
**Préfixe :** `/admin/diving-levels`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/diving-levels` | GET | `index()` | Liste niveaux |
| `/admin/diving-levels/new` | GET/POST | `new()` | Créer niveau |
| `/admin/diving-levels/{id}/edit` | GET/POST | `edit()` | Modifier niveau |
| `/admin/diving-levels/{id}/delete` | POST | `delete()` | Supprimer niveau |

---

### PagesController

**Fichier :** `src/Controller/Admin/PagesController.php` (233 lignes)
**Préfixe :** `/admin/pages`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/pages` | GET | `index()` | Liste pages |
| `/admin/pages/new` | GET/POST | `new()` | Créer page |
| `/admin/pages/{id}/edit` | GET/POST | `edit()` | Modifier page |
| `/admin/pages/{id}/delete` | POST | `delete()` | Supprimer page |

**Services utilisés :**
- `PageTemplateService` pour génération templates

---

### ArticleController

**Fichier :** `src/Controller/Admin/ArticleController.php` (225 lignes)
**Préfixe :** `/admin/articles`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/articles` | GET | `index()` | Liste articles |
| `/admin/articles/new` | GET/POST | `new()` | Créer article |
| `/admin/articles/{id}/edit` | GET/POST | `edit()` | Modifier article |
| `/admin/articles/{id}/delete` | POST | `delete()` | Supprimer article |

**Services utilisés :**
- `ContentSanitizer` pour nettoyage HTML
- `CacheService` pour invalidation cache

---

### GalleryController

**Fichier :** `src/Controller/Admin/GalleryController.php` (333 lignes)
**Préfixe :** `/admin/galleries`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/galleries` | GET | `index()` | Liste galeries |
| `/admin/galleries/new` | GET/POST | `new()` | Créer galerie |
| `/admin/galleries/{id}` | GET | `show()` | Détails galerie + images |
| `/admin/galleries/{id}/edit` | GET/POST | `edit()` | Modifier galerie |
| `/admin/galleries/{id}/upload` | POST | `uploadImages()` | Upload images |
| `/admin/galleries/{id}/images/{imageId}/delete` | POST | `deleteImage()` | Supprimer image |
| `/admin/galleries/{id}/images/reorder` | POST | `reorderImages()` | Réorganiser images |
| `/admin/galleries/{id}/delete` | POST | `delete()` | Supprimer galerie |

**Services utilisés :**
- `ImageUploadService` pour upload et thumbnails

**Complexité :** 333 lignes (le plus gros contrôleur)

---

### AdminUserAttributeController

**Fichier :** `src/Controller/Admin/AdminUserAttributeController.php`
**Préfixe :** `/admin/user-attributes`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/user-attributes/user/{userId}` | GET | `index()` | Attributs d'un user |
| `/admin/user-attributes/user/{userId}/new` | POST | `new()` | Ajouter attribut |
| `/admin/user-attributes/{id}/edit` | POST | `edit()` | Modifier valeur |
| `/admin/user-attributes/{id}/delete` | POST | `delete()` | Supprimer attribut |

---

### AdminAttributeDefinitionController

**Fichier :** `src/Controller/Admin/AdminAttributeDefinitionController.php`
**Préfixe :** `/admin/attribute-definitions`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/attribute-definitions` | GET | `index()` | Liste définitions |
| `/admin/attribute-definitions/new` | GET/POST | `new()` | Créer définition |
| `/admin/attribute-definitions/{id}/edit` | GET/POST | `edit()` | Modifier définition |
| `/admin/attribute-definitions/{id}/delete` | POST | `delete()` | Supprimer définition |

---

### AdminModuleController

**Fichier :** `src/Controller/Admin/AdminModuleController.php`
**Préfixe :** `/admin/modules`
**Accès :** ROLE_SUPER_ADMIN

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/modules` | GET | `index()` | Liste modules |
| `/admin/modules/{id}/toggle` | POST | `toggle()` | Activer/Désactiver module |
| `/admin/modules/{id}/configure` | POST | `configure()` | Configurer module |

**Services utilisés :**
- `ModuleManager`

---

### AdminConfigController

**Fichier :** `src/Controller/Admin/AdminConfigController.php`
**Préfixe :** `/admin/config`

| Route | Méthode | Action | Description |
|-------|---------|--------|-------------|
| `/admin/config` | GET | `index()` | Liste config |
| `/admin/config/{key}/edit` | POST | `edit()` | Modifier valeur config |

**Services utilisés :**
- `SiteConfigService`

---

## 🔐 Matrice d'Autorisation

| Route | Visiteur | USER | DP | ADMIN | SUPER_ADMIN |
|-------|----------|------|-----|-------|-------------|
| `/` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/login` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/register` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/calendrier` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/blog` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/galleries` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/{slug}` (pages) | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/profile` | ❌ | ✅ | ✅ | ✅ | ✅ |
| `/events/{id}/register` | ❌ | ✅ | ✅ | ✅ | ✅ |
| `/dp/*` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `/admin/*` (sauf modules) | ❌ | ❌ | ❌ | ✅ | ✅ |
| `/admin/modules` | ❌ | ❌ | ❌ | ❌ | ✅ |

**Hiérarchie des rôles :**
```
ROLE_SUPER_ADMIN
    └─ ROLE_ADMIN
        └─ ROLE_DP
            └─ ROLE_USER
```

Un SUPER_ADMIN a automatiquement tous les rôles inférieurs.

---

## 📊 Statistiques des Contrôleurs

### Par Complexité (lignes de code)

| Contrôleur | Lignes | Commentaire |
|------------|--------|-------------|
| `GalleryController` | 333 | ⚠️ Trop complexe |
| `DpEventController` | 308 | ⚠️ À simplifier |
| `AdminEventConditionController` | 296 | ⚠️ Logique introspection complexe |
| `AdminEventController` | 282 | ⚠️ Récurrence complexe |
| `AdminUserController` | 280 | ⚠️ Multiples responsabilités |
| `PagesController` | 233 | ✅ Acceptable |
| `ArticleController` | 225 | ✅ Acceptable |

### Par Nombre de Routes

| Contrôleur | Routes | Type |
|------------|--------|------|
| `GalleryController` | 8 | Admin |
| `AdminEventController` | 6 | Admin |
| `BlogController` | 4 | Public |
| `EventRegistrationController` | 3 | User |

---

## 🎯 Recommandations

### Contrôleurs à Refactorer

1. **GalleryController** (333 lignes)
   - Extraire logique upload → `ImageUploadService` (déjà existant, l'utiliser plus)
   - Extraire logique réorganisation → `ImageReorderService`

2. **DpEventController** (308 lignes)
   - Extraire validation inscriptions → `ParticipationValidator`
   - Extraire export → `ParticipantExporter`

3. **AdminEventConditionController** (296 lignes)
   - Extraire form building → `ConditionFormBuilder`

4. **AdminEventController** (282 lignes)
   - Déjà utilise `RecurringEventService` ✅
   - Pourrait extraire logique cascade suppression

5. **AdminUserController** (280 lignes)
   - Extraire workflow approbation → `UserApprovalService`
   - Extraire gestion rôles → `RoleManager`

### Patterns à Appliquer

**1. Service Layer Pattern**

Au lieu de :
```php
class AdminUserController {
    public function approve(User $user) {
        $user->setStatus('approved');
        $user->setActive(true);
        $this->entityManager->flush();
        $this->mailer->send(...);
        $this->addFlash('success', 'User approved');
    }
}
```

Faire :
```php
class AdminUserController {
    public function approve(User $user, UserApprovalService $approvalService) {
        $approvalService->approve($user);
        $this->addFlash('success', 'User approved');
        return $this->redirectToRoute('...');
    }
}

class UserApprovalService {
    public function approve(User $user): void {
        $user->setStatus('approved');
        $user->setActive(true);
        $this->entityManager->flush();
        $this->sendApprovalEmail($user);
    }
}
```

**2. Form Handler Pattern**

Pour éviter duplication logique formulaire :

```php
class AbstractFormController {
    protected function handleFormSubmit(
        FormInterface $form,
        Request $request,
        callable $onSuccess
    ): ?Response {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $onSuccess($form->getData());
        }

        return null;
    }
}
```

---

[➡️ Suite : Services](06-services.md)
