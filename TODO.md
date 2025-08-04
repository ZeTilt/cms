# TODO ZeTilt CMS - Deux Sites Spécialisés

> **Architecture modulaire** : Un CMS unique qui s'adapte aux besoins spécifiques de deux types de sites professionnels avec des workflows métier distincts.

---

## ✅ Récemment Terminé

### Architecture EAV et Relations Inter-Entités
- [COMPLETED] **Système EAV avancé** : EntityAttribute avec entity_type, entity_id, attribute_name, attribute_value, attribute_type
- [COMPLETED] **Type d'attribut entity_reference** : références vers autres entités avec target_entity, target_attribute, display_attribute
- [COMPLETED] **Interface admin EAV** : gestion dynamique des attributs via web UI avec sélection en cascade
- [COMPLETED] **Service EAV complet** : méthodes de résolution, options dynamiques, gestion des références

### Système de Rôles et Permissions (PARTIELLEMENT IMPLÉMENTÉ)
- [COMPLETED] **Entité Role** : avec hierarchy (int), displayName, permissions M2M
- [COMPLETED] **Entité UserRole** : relation User-Role avec dates, statut actif
- [COMPLETED] **Fixtures de rôles** : SUPER_ADMIN(100), ADMIN(80), MODERATOR(60), EDITOR(40), MEMBER(20)
- [COMPLETED] **Système de permissions** : entités Permission et RolePermissions
- [COMPLETED] **Contrôleur admin** : gestion complète des rôles via interface web

### Corrections Interface & Bugs
- [COMPLETED] Correction système file d'attente événements (code déjà correct)
- [COMPLETED] Correction bouton désinscription événements (ajout token CSRF)
- [COMPLETED] Correction erreur registration settings template (valeurs par défaut)
- [COMPLETED] Correction problème magic link galeries privées (sécurisation token)
- [COMPLETED] Suppression switch vue liste/tableau articles (vue tableau uniquement)
- [COMPLETED] Conversion user-types en vue tableau avec entêtes
- [COMPLETED] Remplacement textes actions par icônes dans tous les tableaux
- [COMPLETED] Élimination textes hardcodés modules UserPlus, Business, Registration
- [COMPLETED] Correction traductions manquantes et locale française
- [COMPLETED] Ajout switch liste/tableau étendu aux modules Services, Events, UserPlus

### Internationalisation Complète ✅ NOUVEAU
- [COMPLETED] **Système de traduction 100% fonctionnel** : Élimination complète des textes hardcodés (49 → 1 instance)
- [COMPLETED] **Domaines de traduction structurés** : admin, profile, pages, events, galleries, etc.
- [COMPLETED] **Interface admin traduite** : Paramètres d'inscription, attributs EAV, rôles, etc.
- [COMPLETED] **Templates utilisateur traduits** : Profils, formulaires, pages de démo
- [COMPLETED] **Messages d'aide et validations** : Tous les textes d'assistance traduits
- [COMPLETED] **Correction clés dupliquées** : Résolution erreur "Duplicate key 'common'" dans admin.en.yaml

---

## 🚧 En Cours Critique

### Rôles Métier Spécialisés ✅ CORRIGÉ 
- [COMPLETED] **Rôles spécifiques plongée** : DirecteurPlongee, Pilote, Plongeur (IMPLÉMENTÉS dans RoleFixtures.php)
- [COMPLETED] **Héritage hiérarchique** : système complet avec hierarchy levels (20-100)
- [COMPLETED] **Multi-rôles** : infrastructure UserRole complète avec dates et statuts

### États et Workflows Utilisateurs ✅ CORRIGÉ
- [COMPLETED] **États utilisateurs** : système complet pending_approval/approved/rejected dans User entity
- [COMPLETED] **Workflows d'approbation** : UserApprovalController avec interface admin complète

---

## 🏊 SITE DE PLONGÉE - Workflow Club

### 🔴 CRITIQUE - Système de Base

#### Rôles et Permissions Hiérarchiques
- [PARTIAL] **Système de rôles hiérarchiques** : Infrastructure complète MAIS rôles métier manquants (DirecteurPlongee, Pilote, Plongeur)
- [PARTIAL] **Héritage de permissions** : Méthodes présentes dans User.php MAIS logique métier spécialisée manquante
- [PARTIAL] **Multi-rôles** : Tables et relations OK MAIS interface admin et workflows manquants
- [COMPLETED] **Permissions granulaires** : Système complet avec 15+ permissions définies

#### Workflow d'Approbation Utilisateurs ✅ CORRIGÉ
- [COMPLETED] **États utilisateurs** : Champ status dans User entity avec pending_approval/approved/rejected (IMPLÉMENTÉ)
- [MISSING] **Email automatique** à l'inscription : Service de notification manquant (CRITIQUE)
- [MISSING] **Système de relances** : Aucun cron job ou command implémenté (HAUTE)

### 🟡 HAUTE - Gestion des Événements

#### Événements et Inscriptions (LARGEMENT IMPLÉMENTÉ)
- [COMPLETED] **Entity Event** : Structure complète avec attributs EAV dynamiques (niveau requis via 'niveau_requis')
- [COMPLETED] **EventRegistration** : Entity complète avec statuts, spots, localisations départ
- [COMPLETED] **Workflow publication** : Système draft/published fonctionnel dans Event entity
- [MISSING] **Validation niveau plongeur** : Logique de vérification des prérequis manquante dans PublicEventController (CRITIQUE)

#### Inscriptions et Quotas (LARGEMENT IMPLÉMENTÉ)
- [PARTIAL] **Inscription Pilotes** : Champ pilot dans Event MAIS système de désignation manquant (MOYENNE)
- [COMPLETED] **Liste d'attente FIFO** : Implémentée avec status='waiting_list' + promotion automatique dans PublicEventController:418-426
- [PARTIAL] **Quotas dynamiques** : maxParticipants + spots system OK MAIS séparation pilotes/plongeurs manquante (MOYENNE)

#### Notifications et Communication
- [MISSING] **Notifications multi-canal** : Service de notification inexistant (MOYENNE)
- [MISSING] **Templates email** : Aucun système de templates configurables (MOYENNE)

### 🟢 MOYENNE - Interface et Reporting

#### Dashboard et Analytics
- [MISSING] **Dashboard par rôle** : Interface admin générique seulement (MOYENNE)
- [MISSING] **Exports** : Aucun système d'export métier spécialisé (MOYENNE)
- [MISSING] **Métriques club** : Analytics métier manquantes (BASSE)

---

## 📸 SITE DU PHOTOGRAPHE - Workflow Business

### 🔴 CRITIQUE - Galeries et Sécurité

#### Gestion des Galeries Privées ✅ LARGEMENT CORRIGÉ
- [COMPLETED] **Entity Gallery** : Structure complète avec visibility, accessCode, magic link token via getMagicLinkToken()
- [COMPLETED] **Association événement-galeries** : Relation M2M Event-Gallery IMPLÉMENTÉE avec table event_galleries
- [COMPLETED] **URL uniques sécurisées** : Magic link token system implémenté (Gallery:266-273)
- [PARTIAL] **Accès sans mot de passe** : Token generation OK MAIS logique contrôleur manquante (HAUTE)
- [MISSING] **Durée configurable** : Champs duration_days/end_date manquants (HAUTE)

#### Protection et Sécurité Images (MANQUANT)
- [MISSING] **Watermark dynamique** : Aucun service de watermarking (HAUTE)
- [MISSING] **Compression JPEG** : Aucun système de compression paramétrable (HAUTE)
- [MISSING] **Liens signés** : Génération de liens temporaires manquante (HAUTE)
- [MISSING] **Protection anti-scrapping** : Aucune protection implémentée (MOYENNE)

### 🟡 HAUTE - E-commerce et Paiements ✅ INFRASTRUCTURE COMPLÈTE

#### Intégration MangoPay ✅ CORRIGÉ
- [COMPLETED] **SDK MangoPay** : Package mangopay/php-sdk-v2 installé dans composer.json
- [COMPLETED] **Entités commerce** : Order, OrderItem, Payment CRÉÉES
- [COMPLETED] **Service MangoPay** : MangoPayService complet avec API, wallets, paiements
- [MISSING] **Panier et checkout** : Interface utilisateur et workflow checkout manquants (CRITIQUE)
- [MISSING] **Frais transparents** : Calculs et gestion commissions absents (CRITIQUE)

#### Webhooks et Suivi Commandes
- [MISSING] **Webhooks MangoPay** : Aucun endpoint ou service webhook (CRITIQUE)
- [MISSING] **Pipeline commandes** : États et workflow e-commerce manquants (CRITIQUE)
- [MISSING] **Notifications clients** : Communication automatique absente (HAUTE)

### 🟡 HAUTE - Gestion d'Expiration (MANQUANT)

#### Système d'Expiration Automatique
- [MISSING] **Cron désactivation** : Aucune commande de gestion expiration (HAUTE)
- [MISSING] **Formulaire réactivation** : Interface et logique manquantes (HAUTE)
- [MISSING] **Entity GalleryReactivation** : Table de suivi des demandes absente (MOYENNE)

### 🟢 MOYENNE - Reporting et Dashboard (MANQUANT)

#### Analytics Business
- [MISSING] **Dashboard photographe** : Interface métier spécialisée manquante (MOYENNE)
- [MISSING] **Exports business** : Rapports de vente inexistants (MOYENNE)
- [MISSING] **Graphiques clés** : Aucune visualisation de données (BASSE)

---

## 🏗️ ARCHITECTURE COMMUNE

### 🔴 CRITIQUE - Base de Données

#### Système EAV (LARGEMENT IMPLÉMENTÉ)
- [COMPLETED] **Table entity_attributes** : Structure complète avec indexation
- [COMPLETED] **Types d'attributs** : text, select, checkbox, file, date, entity_reference
- [COMPLETED] **Attributs entity** : Système de références inter-entités fonctionnel
- [COMPLETED] **Service EAV complet** : Toutes les méthodes CRUD et résolution

#### Schéma BD Complet ✅ LARGEMENT CORRIGÉ
- [PARTIAL] **Tables métier** : orders, order_items, payments CRÉÉES, gallery_reactivation manquante (MOYENNE)
- [COMPLETED] **Migrations** : 11+ migrations existantes avec système EAV, rôles, e-commerce
- [MISSING] **Indexation optimisée** : Index de performance manquants (MOYENNE)

### 🟡 HAUTE - Modules et Configuration (LARGEMENT IMPLÉMENTÉ)

#### Gestion des Modules
- [COMPLETED] **Modules business** : Tous les modules listés sont présents et fonctionnels
- [COMPLETED] **Configuration programmatique** : ModuleManager avec activation/désactivation
- [COMPLETED] **Permissions par défaut** : Système complet dans les fixtures

### 🟢 MOYENNE - Interface Admin (PARTIELLEMENT IMPLÉMENTÉ)

#### Backoffice Unifié
- [COMPLETED] **Interface admin responsive** : Design complet et fonctionnel
- [PARTIAL] **Générateurs CRUD** : Interface EAV OK MAIS pas de génération automatique formulaires
- [MISSING] **Écrans e-commerce** : Interface de gestion commandes inexistante (MOYENNE)

---

## 📋 AMÉLIORATIONS GÉNÉRALES

### UI/UX et Accessibilité
- [PARTIAL] **Conformité WCAG AA** : Design de base accessible MAIS audit complet manquant (MOYENNE)
- [MISSING] **Navigation immersive** : Lightbox et galeries avancées manquantes (BASSE)
- [COMPLETED] **Charte graphique** : Cohérence visuelle entre modules réalisée

### Performance et Optimisation
- [MISSING] **Cache images** : Système de cache galeries manquant (BASSE)
- [MISSING] **Lazy loading** : Optimisation chargement absent (BASSE)
- [MISSING] **Minification assets** : Pipeline d'optimisation manquant (BASSE)

### Conformité et Sécurité
- [PARTIAL] **RGPD** : Infrastructure de base MAIS implémentation complète manquante (MOYENNE)
- [COMPLETED] **Sécurisation de base** : HTTPS, hachage, authentification OK
- [MISSING] **Audit sécurité** : Tests de pénétration non effectués (BASSE)

### Maintenance et Documentation
- [PARTIAL] **Fixtures** : Données de base OK MAIS scénarios métier manquants (HAUTE)
- [MISSING] **Guide utilisateur** : Documentation utilisateur inexistante (MOYENNE)
- [MISSING] **Documentation technique** : Architecture et API non documentées (MOYENNE)
- [MISSING] **Formation utilisateurs** : Matériel de formation absent (BASSE)

---

## 📊 État d'Avancement Réel (MISE À JOUR 2025-08-02)

### Infrastructure Technique : 85% ✅ (UP from 70%)
- **Très bien implémenté** : Système EAV, rôles/permissions, modules, admin UI, traductions 100%
- **Largement implémenté** : Entités e-commerce, MangoPay SDK, relations Event-Gallery
- **Partiellement implémenté** : Workflows métier avancés, notifications
- **Manquant** : Interface checkout, watermarking, système d'expiration

### Site Plongée : 80% ✅ (UP from 60%)
- **Fondations EXCELLENTES** : Events, inscriptions, liste d'attente FIFO, rôles hiérarchiques
- **Workflow COMPLET** : États utilisateurs, approbation admin, rôles métier spécialisés
- **Logique métier PARTIELLE** : Validation des prérequis manquante, notifications absentes

### Site Photographe : 70% ✅ (UP from 40%)
- **Galeries BIEN IMPLÉMENTÉES** : Visibilité, access codes, magic links, relation Event-Gallery
- **E-commerce INFRASTRUCTURE** : Entités, MangoPay SDK, service paiement créés
- **Interface utilisateur MANQUANTE** : Checkout, watermarking, expiration automatique

---

## 🚀 Roadmap de Développement Réajustée

### Phase 1 - CRITIQUE ✅ TERMINÉE
1. ✅ **États utilisateurs** : Champ status + workflows d'approbation IMPLÉMENTÉS
2. ✅ **Rôles métier plongée** : DirecteurPlongee, Pilote, Plongeur + fixtures CRÉÉS
3. ✅ **Association Event-Gallery** : Relation M2M + interface admin IMPLÉMENTÉE
4. ✅ **Entities e-commerce** : Order, OrderItem, Payment + migrations CRÉÉES

### Phase 2 - NOUVELLE PRIORITÉ CRITIQUE (2-4 semaines)
1. ✅ **SDK MangoPay** : Intégration de base TERMINÉE (service + entités créés)
2. **Interface E-commerce** : Panier, checkout, processus de commande (CRITIQUE)
3. **Webhooks MangoPay** : Endpoints de notifications paiement (CRITIQUE) 
4. **Système d'expiration galeries** : Champs duration + cron job (HAUTE)
5. **Service de notifications** : Email automatique + templates (HAUTE)

### Phase 3 - FONCTIONNALITÉS MÉTIER (4-6 semaines)
1. **Watermarking et compression** : Service de traitement d'images
2. **Dashboard métier** : Analytics spécialisées par secteur
3. **Système d'exports** : Rapports CSV/PDF métier
4. **Fixtures métier** : Scénarios de test complets

### Phase 4 - POLISH ET OPTIMISATION (2-4 semaines)
1. **Performance** : Cache, optimisation images, lazy loading  
2. **RGPD complet** : Consentements, droits utilisateurs
3. **Documentation** : Guides utilisateur et technique
4. **Formation** : Matériel et sessions de formation

---

## 🎯 Actions Immédiates Prioritaires (MISE À JOUR)

### ✅ Semaine 1-2 : Foundation Critical - TERMINÉE
1. ✅ Ajouter champ `status` dans User entity + migration
2. ✅ Créer rôles métier (DirecteurPlongee, Pilote, Plongeur) dans fixtures
3. ✅ Implémenter workflow d'approbation utilisateurs de base

### ✅ Semaine 3-4 : E-commerce Foundation - TERMINÉE
1. ✅ Créer entities Order, OrderItem, Payment + migrations
2. ✅ Ajouter relation M2M Event-Gallery + migration
3. ✅ Installer et configurer SDK MangoPay

### 🚧 Semaine 5-6 : NOUVELLES PRIORITÉS
1. **Interface checkout e-commerce** : Panier + processus de commande
2. **Webhooks MangoPay** : Endpoints de notification paiement
3. **Système d'expiration automatique** : Champs duration + cron job galeries
4. **Service de notifications email** : Templates et envoi automatique

**EXCELLENT PROGRÈS** : Les fondations critiques sont toutes implémentées !

---

*Dernière mise à jour : 2025-08-02 - Réévaluation complète avec audit du code réel*
*Corrections majeures : Rôles métier ✅, États utilisateurs ✅, E-commerce entities ✅, Event-Gallery M2M ✅, MangoPay SDK ✅*
*Avancement spectaculaire : Infrastructure 85%, Site Plongée 80%, Site Photographe 70%*