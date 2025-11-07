# Dette Technique

[⬅️ Retour à l'index](README.md) | [⬅️ Améliorations](10-ameliorations.md) | [➡️ Guide Maintenance](12-guide-maintenance.md)

## 📊 Inventaire de la Dette Technique

### 🔴 Dette Haute Priorité (À traiter immédiatement)

| Item | Impact | Effort | Fichiers Concernés |
|------|--------|--------|-------------------|
| **Event.php trop complexe** | 🔴 Très Élevé | 🟠 Moyen | `src/Entity/Event.php` (656 lignes) |
| **Codes galerie en clair** | 🔴 Sécurité | 🟢 Faible | `src/Entity/Gallery.php` |
| **Absence de tests** | 🔴 Très Élevé | 🔴 Élevé | Tous les fichiers |
| **Email infrastructure incomplète** | 🟠 Élevé | 🟢 Faible | `src/Controller/RegistrationController.php` |
| **Duplication logique formulaires** | 🟠 Élevé | 🟢 Faible | Tous les contrôleurs Admin |

### 🟠 Dette Moyenne Priorité (Court/Moyen terme)

| Item | Impact | Effort | Description |
|------|--------|--------|-------------|
| **RecurringEventService complexe** | 🟠 Élevé | 🟠 Moyen | Refactor vers Strategy Pattern |
| **Contrôleurs trop gros** | 🟠 Moyen | 🟠 Moyen | GalleryController (333L), DpEventController (308L) |
| **Validation upload insuffisante** | 🟠 Sécurité | 🟢 Faible | `ImageUploadService` |
| **Pas de rate limiting** | 🟠 Sécurité | 🟢 Faible | Configuration security.yaml |
| **Headers sécurité manquants** | 🟠 Sécurité | 🟢 Faible | Configuration serveur |

### 🟡 Dette Basse Priorité (Long terme)

| Item | Impact | Effort | Description |
|------|--------|--------|-------------|
| **Pas de build frontend** | 🟡 Faible | 🟠 Moyen | CDN vs Webpack Encore |
| **Documentation code limitée** | 🟡 Moyen | 🟠 Moyen | PHPDoc manquants |
| **Pas d'analyse statique** | 🟡 Faible | 🟢 Faible | PHPStan/Psalm |
| **CSS non optimisé** | 🟡 Faible | 🟢 Faible | Purge Tailwind |

---

## 📅 Plan de Remboursement

### Sprint 1 (1-2 semaines) - Sécurité Critique

**Objectif :** Corriger vulnérabilités sécurité

1. ✅ **Hasher codes d'accès galerie** (2h)
   - Modifier `Gallery.php`
   - Migration database
   - Adapter formulaires

2. ✅ **Activer login throttling** (30min)
   - Modifier `security.yaml`

3. ✅ **Valider uploads strictement** (2h)
   - Modifier `ImageUploadService`
   - Ajouter tests

4. ✅ **Configurer session sécurisée** (1h)
   - Modifier `framework.yaml`

5. ✅ **Ajouter headers sécurité** (1h)
   - Configuration serveur/bundle

**Temps total :** ~7h

---

### Sprint 2 (2-3 semaines) - Refactoring Event

**Objectif :** Simplifier entité Event

1. ✅ **Créer Value Object EventRecurrence** (4h)
   - `src/ValueObject/EventRecurrence.php`
   - Modifier `Event.php`
   - Migration

2. ✅ **Créer ParticipationManager** (6h)
   - `src/Service/Event/ParticipationManager.php`
   - Extraire logique de Event
   - Adapter contrôleurs
   - Tests

3. ✅ **Créer EligibilityChecker** (4h)
   - `src/Service/Event/EligibilityChecker.php`
   - Extraire logique conditions
   - Tests

**Temps total :** ~14h

---

### Sprint 3 (1-2 semaines) - Refactoring RecurringEventService

**Objectif :** Appliquer Strategy Pattern

1. ✅ **Créer interface RecurrencePattern** (2h)
   - Interface + 3 implémentations
   - Tests

2. ✅ **Refactorer RecurringEventService** (4h)
   - Utiliser patterns
   - Adapter contrôleurs
   - Tests

**Temps total :** ~6h

---

### Sprint 4 (1 semaine) - Contrôleurs

**Objectif :** Simplifier contrôleurs

1. ✅ **AbstractFormController** (3h)
   - Classe de base
   - Adapter contrôleurs

2. ✅ **FlashMessageTrait** (1h)
   - Trait
   - Application

3. ✅ **AbstractRepository** (2h)
   - Classe de base
   - Adapter repos

**Temps total :** ~6h

---

### Sprint 5 (2-3 semaines) - Notifications

**Objectif :** Finaliser système email

1. ✅ **NotificationService** (8h)
   - Service complet
   - Templates email
   - Tests

2. ✅ **Commande rappels** (4h)
   - Command Symfony
   - Cron job
   - Tests

**Temps total :** ~12h

---

### Sprint 6 (2-3 semaines) - Tests

**Objectif :** Augmenter couverture tests

1. ✅ **Tests unitaires services** (12h)
   - RecurringEventService
   - ParticipationManager
   - EligibilityChecker
   - NotificationService

2. ✅ **Tests fonctionnels contrôleurs** (12h)
   - EventController
   - RegistrationController
   - CalendarController

3. ✅ **Tests d'intégration** (8h)
   - Workflow inscription
   - Workflow récurrence
   - Workflow approbation

**Temps total :** ~32h

---

## 💰 Coût Estimé du Remboursement

**Total effort :** ~77 heures développeur

**À taux horaire 50€/h :** 3,850€

**À taux horaire 80€/h :** 6,160€

---

## 🎯 Indicateurs de Suivi

### Métriques de Code

| Métrique | Actuel | Cible | Tool |
|----------|--------|-------|------|
| **Lignes de code** | ~10,000 | -10% | PHPLoc |
| **Complexité cyclomatique** | Moyenne | Basse | PHPMetrics |
| **Duplication** | ~15% | <5% | PHPCPD |
| **Couverture tests** | <10% | >70% | PHPUnit |

### Métriques Qualité

| Métrique | Actuel | Cible |
|----------|--------|-------|
| **Maintenability Index** | 60/100 | >80/100 |
| **Technical Debt Ratio** | 25% | <10% |
| **Code Smells** | Élevé | Faible |

### Métriques Sécurité

| Métrique | Actuel | Cible |
|----------|--------|-------|
| **Vulnérabilités connues** | 3 | 0 |
| **Dépendances obsolètes** | ? | 0 |
| **Security Score** | 6/10 | 9/10 |

---

## 🛠️ Outils Recommandés

### Analyse Statique

```bash
# PHPStan
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyse src --level=6

# Psalm
composer require --dev vimeo/psalm
vendor/bin/psalm --init
vendor/bin/psalm

# PHP-CS-Fixer
composer require --dev friendsofphp/php-cs-fixer
vendor/bin/php-cs-fixer fix src
```

### Métriques

```bash
# PHPMetrics
composer require --dev phpmetrics/phpmetrics
vendor/bin/phpmetrics --report-html=var/metrics src

# PHPLOC
phploc src

# PHPCPD (Copy/Paste Detector)
phpcpd src
```

### Sécurité

```bash
# Security Checker
symfony check:security

# Roave Security Advisories
composer require --dev roave/security-advisories:dev-latest
```

---

## 📈 ROI du Remboursement

### Bénéfices Quantifiables

**Réduction temps développement futur :**
- Nouvelles features : -30% temps
- Bug fixes : -40% temps
- Onboarding nouveaux devs : -50% temps

**Amélioration qualité :**
- Bugs en production : -60%
- Temps debugging : -50%
- Time to market : -25%

**Estimation gain annuel :** 20-40h développeur économisées
**Valeur :** 1,000€ - 3,200€/an

**ROI sur 2 ans :** 200% - 300%

---

## 🎓 Prévention Dette Future

### Règles à Adopter

1. **Pas de classe > 300 lignes**
   - Extraction systématique

2. **Couverture tests ≥ 70%**
   - CI/CD rejette <70%

3. **Code review obligatoire**
   - 2 reviewers minimum

4. **Analyse statique en CI**
   - PHPStan level 6 minimum
   - Pas d'erreurs tolérées

5. **Documentation code**
   - PHPDoc sur méthodes publiques
   - README par module

6. **Dependency updates**
   - Review mensuel composer outdated
   - Security patches sous 48h

### Processus

**Avant chaque commit :**
```bash
vendor/bin/phpstan analyse src
vendor/bin/phpunit
vendor/bin/php-cs-fixer fix src --dry-run
```

**Avant chaque PR :**
```bash
vendor/bin/phpmetrics src
vendor/bin/phpcpd src
symfony check:security
```

**Chaque sprint :**
- Review dette technique backlog
- Planifier 20% temps remboursement

---

[➡️ Suite : Guide Maintenance](12-guide-maintenance.md)
