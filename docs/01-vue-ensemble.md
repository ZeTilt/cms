# Vue d'Ensemble - Application Club Subaquatique des Vénètes

[⬅️ Retour à l'index](README.md)

## 🎯 Objectif de l'Application

L'application de gestion du Club Subaquatique des Vénètes est un **système complet de gestion de club de plongée** conçu pour faciliter l'organisation des activités, la gestion des membres, et la communication au sein du club.

### Mission Principale

Centraliser et automatiser la gestion d'un club de plongée en offrant :
- Un **calendrier d'événements** accessible à tous les membres
- Un **système d'inscription** aux sorties et formations
- Une **gestion des membres** avec leurs niveaux de certification
- Un **espace de communication** via blog et pages d'information
- Des **outils administratifs** pour les dirigeants du club

## 👥 Utilisateurs Cibles

L'application s'adresse à quatre profils d'utilisateurs distincts :

### 1. Visiteurs (Non Authentifiés)
**Objectifs :**
- Découvrir le club
- Consulter le calendrier des événements
- Lire les articles de blog
- S'inscrire comme membre

**Accès :**
- Pages d'information (qui sommes-nous, tarifs, etc.)
- Calendrier public en lecture seule
- Blog et galeries publiques
- Formulaire d'inscription

### 2. Membres (ROLE_USER)
**Objectifs :**
- S'inscrire aux événements (sorties plongée, formations)
- Consulter son profil et ses inscriptions
- Accéder aux informations du club

**Accès :**
- Toutes les fonctionnalités visiteur +
- Inscription/désinscription aux événements
- Profil personnel
- Galeries privées (avec code d'accès)

### 3. Directeurs de Plongée (ROLE_DP)
**Objectifs :**
- Organiser les sorties plongée
- Gérer les participants et leurs niveaux
- Valider l'éligibilité des plongeurs

**Accès :**
- Toutes les fonctionnalités membre +
- Interface dédiée DP
- Gestion avancée des événements de plongée
- Vue détaillée des participants par niveau

### 4. Administrateurs (ROLE_ADMIN / ROLE_SUPER_ADMIN)
**Objectifs :**
- Gérer tous les aspects du site
- Créer et modifier le contenu
- Gérer les membres et leurs droits
- Configurer les paramètres du système

**Accès :**
- Interface d'administration complète
- CRUD sur toutes les entités
- Gestion des utilisateurs et rôles
- Configuration des modules
- Gestion du contenu (pages, blog, galeries)

## 🎨 Cas d'Usage Principaux

### Cas d'Usage #1 : Organisation d'une Sortie Plongée

**Acteurs :** Administrateur, Directeur de Plongée, Membres

**Scénario :**
1. L'administrateur crée un événement "Sortie plongée épave" :
   - Type : Sortie plongée (couleur bleue)
   - Date : Samedi 15 juin 2025
   - Lieu : Port de Vannes → Site de plongée Arradon
   - Heures de rendez-vous :
     - 8h30 au club
     - 9h30 sur le site
   - Places : 12 maximum
   - Niveau minimum : PA20 (Plongeur Autonome 20m)

2. L'événement est publié sur le calendrier

3. Les membres s'inscrivent :
   - Jean (PA40) s'inscrit → accepté immédiatement
   - Marie (PE12) tente de s'inscrire → refusée (niveau insuffisant)
   - 12 plongeurs s'inscrivent
   - Le 13ème plongeur est mis en liste d'attente

4. Un plongeur se désinscrit :
   - Le système promeut automatiquement le premier de la liste d'attente

5. Le DP consulte la liste des participants :
   - Voit les 12 participants groupés par niveau
   - Vérifie les points de rendez-vous choisis
   - Peut exporter la liste

### Cas d'Usage #2 : Création d'une Formation Récurrente

**Acteurs :** Administrateur

**Scénario :**
1. Création d'une série "Formation Niveau 1" :
   - Tous les mercredis soirs
   - De 18h à 20h
   - Du 1er septembre au 30 novembre
   - Lieu : Piscine municipale
   - 15 places maximum

2. Le système génère automatiquement :
   - 13 événements individuels
   - Tous liés à l'événement parent
   - Chacun avec sa propre gestion d'inscriptions

3. Modification de la série :
   - L'administrateur peut modifier l'événement parent
   - Ou supprimer tous les événements futurs à partir d'une date

### Cas d'Usage #3 : Gestion du Contenu du Site

**Acteurs :** Administrateur

**Scénario :**
1. Création d'une page "Nos Partenaires" :
   - Rédaction du contenu
   - Génération automatique du template Twig
   - Publication
   - URL : `/nos-partenaires`

2. Ajout d'un article de blog :
   - "Compte-rendu sortie épave du 15 juin"
   - Upload d'images
   - Catégorie : Sorties
   - Tags : épave, plongée profonde
   - Publication immédiate

3. Création d'une galerie photo :
   - Titre : "Sortie Arradon - Juin 2025"
   - Upload de 30 photos
   - Génération automatique de thumbnails
   - Accès privé avec code
   - Partage du code aux participants

### Cas d'Usage #4 : Inscription d'un Nouveau Membre

**Acteurs :** Visiteur, Administrateur

**Scénario :**
1. Le visiteur s'inscrit :
   - Remplit le formulaire (nom, prénom, email, mot de passe)
   - Indique son niveau de plongée : PA20
   - Valide l'inscription

2. Le système :
   - Crée un compte avec statut "pending"
   - Envoie un email de vérification
   - Notifie les administrateurs

3. L'utilisateur vérifie son email :
   - Clique sur le lien de vérification
   - Email marqué comme vérifié

4. L'administrateur examine la demande :
   - Vérifie les informations
   - Approuve le compte
   - Le membre peut maintenant se connecter et s'inscrire aux événements

### Cas d'Usage #5 : Utilisation du Système de Conditions

**Acteurs :** Administrateur

**Scénario :**
1. Création d'une sortie plongée profonde (40m) :
   - Ajout de conditions d'éligibilité :
     - Niveau minimum : PA40
     - Attribut personnalisé "Assurance" = "Valide"
     - Attribut "Certificat médical" existe et non expiré

2. Tentative d'inscription d'un membre :
   - Le système vérifie toutes les conditions
   - Si une condition échoue → message d'erreur explicatif
   - Si toutes passent → inscription validée

## 🌟 Fonctionnalités Clés

### Gestion des Événements
- ✅ Création d'événements simples ou récurrents
- ✅ Types d'événements personnalisables avec couleurs
- ✅ Gestion des capacités et listes d'attente
- ✅ Deux points de rendez-vous (club + site)
- ✅ Conditions d'éligibilité dynamiques
- ✅ Système de confirmation des participants

### Gestion Spécifique Plongée
- ✅ Niveaux de certification
- ✅ Vérification automatique des prérequis
- ✅ Interface dédiée Directeur de Plongée
- ✅ Vue participants par niveau
- ✅ Gestion des prérequis par sortie

### Gestion des Membres
- ✅ Inscription avec validation email
- ✅ Workflow d'approbation des comptes
- ✅ Système de rôles hiérarchisés
- ✅ Profils personnalisables avec attributs EAV
- ✅ Gestion des niveaux de plongée

### Système de Contenu
- ✅ CMS Pages : création de pages statiques
- ✅ Blog : articles avec catégories et tags
- ✅ Galeries : gestion de photos avec accès privé
- ✅ Système modulaire : activation/désactivation par module

### Administration
- ✅ Interface d'administration complète
- ✅ Dashboard centralisé
- ✅ CRUD sur toutes les entités
- ✅ Configuration du site
- ✅ Gestion des types d'événements

## 📊 Statistiques de l'Application

### Entités de Domaine
- **14 entités** principales
- **100+ routes** HTTP
- **28 contrôleurs**
- **14+ services** métier
- **60+ templates** Twig

### Complexité
- Entité la plus complexe : **Event** (656 lignes)
- Contrôleur le plus complexe : **GalleryController** (333 lignes)
- Service le plus élaboré : **RecurringEventService** (254 lignes)

### Architecture
- **MVC** classique Symfony
- **DDD** partiel (séparation domaine/infrastructure)
- **Service-oriented** pour la logique métier
- **EAV** pour l'extensibilité

## 🎭 Particularités et Innovations

### 1. Système EAV (Entity-Attribute-Value)
Permet d'ajouter des attributs personnalisés aux entités sans modifier le schéma de base de données. Utile pour des champs spécifiques comme :
- Numéro de licence fédérale
- Date de validité du certificat médical
- Numéro d'assurance
- etc.

### 2. Système de Conditions d'Événements
Moteur de règles flexible permettant de définir dynamiquement qui peut s'inscrire à un événement :
- Conditions sur les attributs d'entité
- Opérateurs variés (=, !=, >, <, contains, in, exists)
- Messages d'erreur personnalisables
- Combinaisons multiples

### 3. Gestion Intelligente des Listes d'Attente
- Promotion automatique quand une place se libère
- Notifications (infrastructure prête)
- Historique des participations

### 4. Événements Récurrents Avancés
- Patterns multiples (quotidien, hebdomadaire, mensuel)
- Jours de la semaine spécifiques
- Date de fin flexible
- Gestion en cascade des modifications/suppressions

### 5. Système Modulaire
- Activation/désactivation de fonctionnalités
- Configuration par module en JSON
- Navigation dynamique
- Préparé pour extensions futures

## 🔍 Comparaison avec des Solutions Alternatives

| Fonctionnalité | Cette Application | Doodle | Meetup | WordPress + Plugin |
|----------------|-------------------|--------|--------|-------------------|
| Événements récurrents | ✅ Avancé | ❌ | ✅ Basique | ✅ Variable |
| Gestion niveaux plongée | ✅ Natif | ❌ | ❌ | ⚠️ Nécessite custom |
| Conditions d'éligibilité | ✅ Dynamique | ❌ | ❌ | ❌ |
| Liste d'attente auto | ✅ | ❌ | ✅ | ⚠️ Variable |
| Système EAV | ✅ | ❌ | ❌ | ⚠️ Via plugins |
| CMS intégré | ✅ | ❌ | ⚠️ Limité | ✅ |
| Interface DP dédiée | ✅ | ❌ | ❌ | ❌ |
| Auto-hébergement | ✅ | ❌ | ❌ | ✅ |
| Coût | Gratuit | Gratuit/Payant | Payant | Gratuit/Payant |

**Avantage principal :** Solution métier complète spécifiquement conçue pour les clubs de plongée, contrairement aux solutions génériques.

## 🎯 Positionnement

Cette application se positionne comme une **solution métier spécialisée** pour clubs de plongée, offrant :

✅ **Avantages :**
- Fonctionnalités métier natives (niveaux, DP, conditions)
- Flexibilité et personnalisation complète
- Pas de dépendance à un SaaS tiers
- Données hébergées en propre
- Évolutivité illimitée

⚠️ **Limites actuelles :**
- Pas d'application mobile native
- Notifications limitées (infrastructure présente mais incomplète)
- Pas de paiement en ligne intégré
- Pas d'export iCal pour calendriers externes
- Interface en français uniquement

## 📈 Évolution et Roadmap Potentielle

### Court terme (1-3 mois)
- Finaliser le système de notifications email
- Ajouter export iCal/PDF
- Améliorer les tests automatisés

### Moyen terme (3-6 mois)
- Application mobile (PWA progressive)
- Système de paiement en ligne
- API REST documentée
- Statistiques et rapports

### Long terme (6-12 mois)
- Multi-langue (i18n)
- Système de messagerie interne
- Intégration réseaux sociaux
- Gestion de matériel/équipement

---

[➡️ Suite : Architecture Technique](02-architecture-technique.md)
