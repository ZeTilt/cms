# TODO ZeTilt CMS

## En Cours
- [IN PROGRESS] Éliminer TOUT texte hardcodé des fichiers PHP et Twig (high)
- [IN PROGRESS] Continuer élimination texte hardcodé restant (305 instances) (medium)

## Terminé ✅
- [COMPLETED] Étendre switch liste/tableau aux autres modules (Services, Events, UserPlus) (high)
- [COMPLETED] Corriger erreurs JavaScript dans switch vue Articles/Services (high)
- [COMPLETED] Garder seulement vue table pour Events et UserPlus (supprimer vue liste) (medium)
- [COMPLETED] Corriger traductions manquantes UserPlus (clés affichées) (high)
- [COMPLETED] Corriger traductions manquantes Business (clés affichées) (high)
- [COMPLETED] Corriger locale Registration (affiche anglais au lieu français) (high)
- [COMPLETED] Ajouter switch liste/tableau au module UserPlus (medium)
- [COMPLETED] Corriger dropdown sélection langue en mode admin (medium)

## À Faire
### Interface & UI
- [PENDING] Penser à commit et push régulièrement les changements une fois l'item terminé (medium)

### Données & Tests
- [PENDING] Faire des fixtures complètes avec beaucoup de données pour tout tester (high)
- [PENDING] Faire un seul fichier de migration au lieu de la quantité énorme actuelle (high)

### Système Core
- [PENDING] Implémenter système EAV pour attributs dynamiques (high)
- [PENDING] Créer système de rôles hiérarchiques (SuperAdmin > Admin > DirecteurPlongee > Pilote > Plongeur) (high)
- [PENDING] Implémenter multi-rôles avec cumul de droits (medium)
- [PENDING] Ajouter système de permissions granulaires par module et utilisateur (high)
- [PENDING] Créer workflow d'approbation utilisateurs (Draft > PendingApproval > Approved/Rejected) (high)
- [PENDING] Implémenter gestion attributs de type entity avec filtres (medium)

### Événements & Notifications
- [PENDING] Développer système d'événements avec quotas et liste d'attente FIFO (high)
- [PENDING] Ajouter notifications email et in-app avec système de relances (medium)
- [PENDING] Créer dashboard par rôle avec filtres et exports CSV/PDF (medium)

### Galeries & Photos
- [PENDING] Implémenter galeries privées avec URL uniques pour photographes (high)
- [PENDING] Ajouter système de watermark et compression d'images configurable (medium)
- [PENDING] Créer système d'expiration et réactivation galeries (120j par défaut) (medium)
- [PENDING] Ajouter cron pour désactivation automatique galeries expirées (low)
- [PENDING] Développer formulaire de réactivation avec envoi auto email (low)

### E-commerce & Paiements
- [PENDING] Intégrer MangoPay pour paiements (1,8% + 0,18€/transaction) (high)
- [PENDING] Développer système de panier et checkout pour vente photos (medium)
- [PENDING] Implémenter webhooks MangoPay (PAYIN_SUCCEEDED, PAYOUT_SUCCEEDED) (medium)
- [PENDING] Créer reporting CA/ventes avec graphiques React/Chart (medium)

### Sécurité & Performance
- [PENDING] Implémenter protection anti-scrapping et liens signés (medium)
- [PENDING] Ajouter conformité WCAG AA pour accessibilité (medium)

### Communication
- [PENDING] Créer templates email personnalisables avec délais configurables (low)

### Architecture & BD
- [PENDING] Définir schéma BD complet (gallery_reactivation, orders, permissions) (high)
- [PENDING] Concevoir écrans backoffice e-commerce et reporting (medium)

## Corrections Récentes
- ✅ Correction template Registration - Remplacement textes hardcodés par traductions
- ✅ Ajout clés manquantes registration.fr.yaml et registration.en.yaml
- ✅ Fix erreurs JavaScript modules Articles et Services
- ✅ Simplification module Events (vue table seulement)
- ✅ Correction positionnement dropdown langue (s'affiche vers le haut si nécessaire)
- ✅ Traductions page d'accueil (home.fr.yaml et home.en.yaml)
- ✅ Ajout traductions manquantes module Certifications

## Notes
- L'élimination des textes hardcodés est une priorité haute
- Les traductions manquantes UserPlus, Business et Registration ont été corrigées
- Système de switch liste/tableau étendu à tous les modules