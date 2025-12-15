# Cahier de Recette - Club de Plongée Vénètes

**Version**: 1.0
**Date**: 09/12/2025
**Application**: Site web du Club de Plongée Vénètes
**Stack**: Symfony 7 / PHP 8.3 / MySQL

---

## Table des matières

1. [Prérequis et environnement](#1-prérequis-et-environnement)
2. [Fonctionnalités publiques](#2-fonctionnalités-publiques)
3. [Authentification](#3-authentification)
4. [Profil utilisateur](#4-profil-utilisateur)
5. [Participation aux événements](#5-participation-aux-événements)
6. [Administration](#6-administration)
7. [Espace Directeur de Plongée](#7-espace-directeur-de-plongée)
8. [Notifications push](#8-notifications-push)
9. [API et intégrations](#9-api-et-intégrations)
10. [Tests de performance](#10-tests-de-performance)
11. [Tests de sécurité](#11-tests-de-sécurité)

---

## 1. Prérequis et environnement

### 1.1 Comptes de test

| Rôle | Email | Mot de passe | Usage |
|------|-------|--------------|-------|
| Super Admin | superadmin@test.fr | Test1234! | Tests admin complets |
| Admin | admin@test.fr | Test1234! | Tests admin standard |
| DP | dp@test.fr | Test1234! | Tests directeur plongée |
| Utilisateur | user@test.fr | Test1234! | Tests membre standard |
| Nouveau | nouveau@test.fr | Test1234! | Tests inscription/pending |

### 1.2 Données de test

- [ ] Au moins 5 événements futurs de types différents
- [ ] Au moins 3 articles publiés
- [ ] Au moins 2 galeries avec images
- [ ] Au moins 5 utilisateurs avec différents statuts
- [ ] Niveaux de plongée et d'apnée configurés

---

## 2. Fonctionnalités publiques

### 2.1 Page d'accueil

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 2.1.1 | Accès à `/` | Page d'accueil s'affiche | | | |
| 2.1.2 | Articles récents | 5 derniers articles publiés affichés | | | |
| 2.1.3 | Événements à venir | Widget avec 4 prochains événements | | | |
| 2.1.4 | Carousel articles | Navigation fonctionnelle | | | |
| 2.1.5 | Informations contact | Adresse, téléphone, email visibles | | | |
| 2.1.6 | Responsive mobile | Affichage correct sur mobile | | | |

### 2.2 Blog

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 2.2.1 | Accès `/blog` | Liste des articles paginée (6/page) | | | |
| 2.2.2 | Pagination | Navigation entre pages | | | |
| 2.2.3 | Article individuel `/blog/article/{slug}` | Contenu complet affiché | | | |
| 2.2.4 | Filtre par catégorie | Articles filtrés correctement | | | |
| 2.2.5 | Filtre par tag | Articles filtrés correctement | | | |
| 2.2.6 | Image mise en avant | Image affichée avec alt et caption | | | |
| 2.2.7 | Articles brouillon | Non visibles publiquement | | | |

### 2.3 Pages statiques

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 2.3.1 | Page "Qui sommes-nous" | Contenu affiché | | | |
| 2.3.2 | Page "Où nous trouver" | Carte/adresse affichée | | | |
| 2.3.3 | Page "Tarifs" | Grille tarifaire visible | | | |
| 2.3.4 | Page "Nos partenaires" | Logos partenaires | | | |
| 2.3.5 | Page "Nos activités" | Description activités | | | |
| 2.3.6 | Pages non publiées | Erreur 404 | | | |
| 2.3.7 | SEO meta tags | Title et description présents | | | |

### 2.4 Galeries

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 2.4.1 | Liste galeries `/galleries` | Toutes galeries publiques | | | |
| 2.4.2 | Galerie individuelle | Images affichées en grille | | | |
| 2.4.3 | Image individuelle | Lightbox/vue détaillée | | | |
| 2.4.4 | Navigation images | Précédent/suivant fonctionnel | | | |
| 2.4.5 | Galeries privées | Non visibles publiquement | | | |

### 2.5 Calendrier

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 2.5.1 | Accès `/calendrier` | Calendrier du mois courant | | | |
| 2.5.2 | Navigation mois | Mois précédent/suivant | | | |
| 2.5.3 | Navigation année | Changement d'année | | | |
| 2.5.4 | Événements sur calendrier | Points colorés par type | | | |
| 2.5.5 | Liste événements à venir | 5 prochains événements | | | |
| 2.5.6 | Détail événement | Toutes infos affichées | | | |
| 2.5.7 | Événements annulés | Marqués comme annulés | | | |
| 2.5.8 | Places disponibles | Compteur affiché | | | |

### 2.6 Formulaire de contact

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 2.6.1 | Accès `/contact` | Formulaire affiché | | | |
| 2.6.2 | Champs requis vides | Message d'erreur | | | |
| 2.6.3 | Email invalide | Message d'erreur | | | |
| 2.6.4 | Soumission valide | Message de succès | | | |
| 2.6.5 | Email au club | Email reçu par le club | | | |
| 2.6.6 | Email confirmation | Email reçu par l'expéditeur | | | |
| 2.6.7 | Protection spam (honeypot) | Soumission bot bloquée | | | |

---

## 3. Authentification

### 3.1 Connexion

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 3.1.1 | Accès `/login` | Formulaire connexion | | | |
| 3.1.2 | Identifiants corrects | Connexion réussie, redirection | | | |
| 3.1.3 | Identifiants incorrects | Message d'erreur | | | |
| 3.1.4 | Compte inactif | Connexion refusée | | | |
| 3.1.5 | Mémorisation email | Email pré-rempli après erreur | | | |
| 3.1.6 | Lien "Mot de passe oublié" | Redirection vers reset | | | |
| 3.1.7 | Déconnexion | Session terminée | | | |

### 3.2 Inscription

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 3.2.1 | Accès `/inscription` | Formulaire inscription | | | |
| 3.2.2 | Champs requis | Validation présente | | | |
| 3.2.3 | Email existant | Message d'erreur | | | |
| 3.2.4 | Mot de passe < 8 car | Message d'erreur | | | |
| 3.2.5 | Inscription valide | Compte créé, statut "pending" | | | |
| 3.2.6 | Email de bienvenue | Email avec lien vérification | | | |
| 3.2.7 | Vérification email | Clic lien → email vérifié | | | |
| 3.2.8 | Token expiré | Message d'erreur approprié | | | |

### 3.3 Réinitialisation mot de passe

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 3.3.1 | Accès `/mot-de-passe-oublie` | Formulaire affiché | | | |
| 3.3.2 | Email existant | Email de réinitialisation envoyé | | | |
| 3.3.3 | Email inexistant | Message générique (sécurité) | | | |
| 3.3.4 | Lien de reset valide | Formulaire nouveau mdp | | | |
| 3.3.5 | Token expiré (>1h) | Message d'erreur | | | |
| 3.3.6 | Nouveau mdp identique | Refusé | | | |
| 3.3.7 | Reset réussi | Connexion avec nouveau mdp | | | |

---

## 4. Profil utilisateur

### 4.1 Informations générales

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 4.1.1 | Accès `/profile` | Page profil affichée | | | |
| 4.1.2 | Infos utilisateur | Nom, email, statut visibles | | | |
| 4.1.3 | Date d'inscription | Affichée correctement | | | |
| 4.1.4 | Compte pending | Alerte affichée | | | |
| 4.1.5 | Bouton "Relancer admins" | Email envoyé aux admins | | | |

### 4.2 Avatar

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 4.2.1 | Upload avatar (JPG) | Image enregistrée | | | |
| 4.2.2 | Upload avatar (PNG) | Image enregistrée | | | |
| 4.2.3 | Upload avatar (WebP) | Image enregistrée | | | |
| 4.2.4 | Fichier > 2 Mo | Message d'erreur | | | |
| 4.2.5 | Format non autorisé | Message d'erreur | | | |
| 4.2.6 | Suppression avatar | Avatar supprimé | | | |
| 4.2.7 | Remplacement avatar | Ancien supprimé, nouveau affiché | | | |

### 4.3 Activités et niveaux

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 4.3.1 | Cocher "Plongeur" | Section niveau visible | | | |
| 4.3.2 | Sélectionner niveau plongée | Niveau enregistré | | | |
| 4.3.3 | Cocher "Apnéiste" | Section niveau apnée visible | | | |
| 4.3.4 | Sélectionner niveau apnée | Niveau enregistré | | | |
| 4.3.5 | Cocher "Pilote" | Option enregistrée | | | |
| 4.3.6 | Décocher activité | Niveau correspondant effacé | | | |

### 4.4 Informations personnelles

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 4.4.1 | Saisie téléphone | Numéro enregistré | | | |
| 4.4.2 | Saisie date naissance | Date enregistrée | | | |
| 4.4.3 | Saisie adresse | Adresse enregistrée | | | |
| 4.4.4 | Date invalide | Message d'erreur | | | |

### 4.5 CACI (Certificat médical)

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 4.5.1 | Aucun CACI | Statut "Aucun CACI déclaré" jaune | | | |
| 4.5.2 | Déclarer date expiration | Statut "En attente vérification" | | | |
| 4.5.3 | Cocher attestation | Obligatoire pour soumettre | | | |
| 4.5.4 | Date dans le passé | Statut "Expiré" rouge | | | |
| 4.5.5 | Après vérification DP | Statut "Valide" vert | | | |
| 4.5.6 | Modifier date après vérif | Vérification réinitialisée | | | |
| 4.5.7 | Infos vérificateur | Nom DP et date affichés | | | |

### 4.6 Cotisation club

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 4.6.1 | Aucune cotisation | Statut "Non réglée" jaune | | | |
| 4.6.2 | Cotisation expirée | Statut "Expirée" rouge | | | |
| 4.6.3 | Cotisation à jour | Statut "À jour" vert | | | |
| 4.6.4 | Infos cotisation | Saison, montant, date, valideur | | | |
| 4.6.5 | Saison affichée | Format "2024-2025" | | | |

### 4.7 Licence FFESSM

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 4.7.1 | Aucune licence | Alerte affichée | | | |
| 4.7.2 | Upload justificatif (PDF) | Fichier enregistré | | | |
| 4.7.3 | Upload justificatif (JPG) | Fichier enregistré | | | |
| 4.7.4 | Fichier > 5 Mo | Message d'erreur | | | |
| 4.7.5 | Numéro de licence | Enregistré et affiché | | | |
| 4.7.6 | Date expiration | Enregistrée | | | |
| 4.7.7 | Licence expirée | Statut rouge | | | |
| 4.7.8 | Voir justificatif | Téléchargement/affichage | | | |

### 4.8 Notifications push

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 4.8.1 | Navigateur compatible | Bouton "Activer" visible | | | |
| 4.8.2 | Activer notifications | Permission demandée | | | |
| 4.8.3 | Accepter permission | Statut "Activées" | | | |
| 4.8.4 | Préférences visibles | Checkboxes affichées | | | |
| 4.8.5 | Modifier préférences | Changements sauvegardés | | | |
| 4.8.6 | Désactiver notifications | Statut "Désactivées" | | | |

---

## 5. Participation aux événements

### 5.1 Inscription à un événement

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 5.1.1 | Non connecté | Bouton "Se connecter" | | | |
| 5.1.2 | Compte non approuvé | Inscription impossible | | | |
| 5.1.3 | CACI manquant/expiré | Inscription impossible + message | | | |
| 5.1.4 | Cotisation non payée | Inscription impossible + message | | | |
| 5.1.5 | Niveau insuffisant | Inscription impossible + message | | | |
| 5.1.6 | Inscription valide | Confirmation + flash message | | | |
| 5.1.7 | Sélection point RDV | Club ou site | | | |
| 5.1.8 | Déjà inscrit | Message "Déjà inscrit" | | | |
| 5.1.9 | Événement complet | Inscription en liste d'attente | | | |
| 5.1.10 | Place libérée | Promotion auto liste d'attente | | | |

### 5.2 Désinscription

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 5.2.1 | Bouton désinscription | Visible si inscrit | | | |
| 5.2.2 | Confirmer désinscription | Participation annulée | | | |
| 5.2.3 | Liste d'attente promu | Premier de la liste inscrit | | | |

### 5.3 Mes inscriptions

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 5.3.1 | Accès `/events/my-registrations` | Liste mes inscriptions | | | |
| 5.3.2 | Statuts affichés | Inscrit / Liste d'attente | | | |
| 5.3.3 | Lien vers événement | Clic → détail événement | | | |

---

## 6. Administration

### 6.1 Dashboard

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.1.1 | Accès `/admin` | Dashboard affiché | | | |
| 6.1.2 | Stats utilisateurs | Total, actifs, pending | | | |
| 6.1.3 | Nouveaux ce mois | Compteur correct | | | |
| 6.1.4 | Événements à venir | 5 prochains listés | | | |
| 6.1.5 | Alertes événements | Peu d'inscrits / presque complet | | | |
| 6.1.6 | Accès sans ROLE_ADMIN | Accès refusé | | | |

### 6.2 Gestion des utilisateurs

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.2.1 | Liste utilisateurs | Tous les utilisateurs | | | |
| 6.2.2 | Filtrage par statut | Filtre fonctionnel | | | |
| 6.2.3 | Créer utilisateur | Formulaire + création | | | |
| 6.2.4 | Modifier utilisateur | Modifications enregistrées | | | |
| 6.2.5 | Supprimer utilisateur | Utilisateur supprimé | | | |
| 6.2.6 | Supprimer soi-même | Interdit | | | |
| 6.2.7 | Approuver compte pending | Statut → approved | | | |
| 6.2.8 | Rejeter compte | Statut → rejected | | | |
| 6.2.9 | Assigner rôles | ROLE_USER, ROLE_ADMIN, ROLE_DP | | | |
| 6.2.10 | Email approbation | Email envoyé à l'utilisateur | | | |

### 6.3 Gestion des événements

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.3.1 | Liste événements | Tous les événements | | | |
| 6.3.2 | Créer événement simple | Événement créé | | | |
| 6.3.3 | Créer événement récurrent | Série créée | | | |
| 6.3.4 | Récurrence hebdo | Événements créés chaque semaine | | | |
| 6.3.5 | Modifier événement | Modifications sauvées | | | |
| 6.3.6 | Supprimer événement | Événement supprimé | | | |
| 6.3.7 | Supprimer série | Toute la série supprimée | | | |
| 6.3.8 | Assigner DP | DP assigné | | | |
| 6.3.9 | Assigner pilote | Pilote assigné | | | |
| 6.3.10 | Assigner bateau | Bateau assigné | | | |
| 6.3.11 | Niveau minimum | Niveau requis enregistré | | | |
| 6.3.12 | Annuler événement | Statut → cancelled | | | |

### 6.4 Types d'événements

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.4.1 | Liste types | Tous les types | | | |
| 6.4.2 | Créer type | Type créé avec couleur | | | |
| 6.4.3 | Modifier type | Modifications sauvées | | | |
| 6.4.4 | Supprimer type | Type supprimé | | | |
| 6.4.5 | Type avec événements | Suppression bloquée ou cascade | | | |
| 6.4.6 | Couleur sur calendrier | Événements colorés | | | |

### 6.5 Gestion des bateaux

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.5.1 | Liste bateaux | Tous les bateaux | | | |
| 6.5.2 | Créer bateau | Bateau créé | | | |
| 6.5.3 | Capacité | Nombre enregistré | | | |
| 6.5.4 | Désactiver bateau | Non disponible pour événements | | | |

### 6.6 Niveaux de plongée

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.6.1 | Accès (ROLE_SUPER_ADMIN) | Liste niveaux | | | |
| 6.6.2 | Accès (ROLE_ADMIN) | Accès refusé | | | |
| 6.6.3 | Créer niveau | Niveau créé | | | |
| 6.6.4 | Ordre de tri | Ordre respecté | | | |
| 6.6.5 | Flag instructeur | Option fonctionnelle | | | |

### 6.7 Gestion des pages

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.7.1 | Liste pages | Toutes les pages | | | |
| 6.7.2 | Créer page | Page créée, slug auto | | | |
| 6.7.3 | Éditeur classique | Contenu HTML enregistré | | | |
| 6.7.4 | Éditeur blocs | Blocs ajoutés/modifiés | | | |
| 6.7.5 | Convertir en blocs | Conversion réussie | | | |
| 6.7.6 | Publier page | Statut → published | | | |
| 6.7.7 | Prévisualiser | Aperçu avant publication | | | |
| 6.7.8 | Image mise en avant | Upload fonctionnel | | | |
| 6.7.9 | Meta SEO | Title/description enregistrés | | | |
| 6.7.10 | Supprimer page | Page supprimée | | | |

### 6.8 Gestion des articles

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.8.1 | Liste articles | Pagination (10/page) | | | |
| 6.8.2 | Créer article | Article créé | | | |
| 6.8.3 | Éditeur blocs | Tous types de blocs | | | |
| 6.8.4 | Catégorie | Sélection catégorie | | | |
| 6.8.5 | Tags | Ajout/suppression tags | | | |
| 6.8.6 | Publier/Dépublier | Toggle statut | | | |
| 6.8.7 | Date publication | Auto à la publication | | | |
| 6.8.8 | Excerpt | Auto-généré si vide | | | |

### 6.9 Éditeur de blocs de contenu

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.9.1 | Bloc Texte | Texte riche enregistré | | | |
| 6.9.2 | Bloc Image | Image + alt + caption | | | |
| 6.9.3 | Bloc Galerie | Galerie sélectionnée | | | |
| 6.9.4 | Bloc Vidéo | URL vidéo | | | |
| 6.9.5 | Bloc Citation | Texte + auteur | | | |
| 6.9.6 | Bloc Accordéon | Sections repliables | | | |
| 6.9.7 | Bloc CTA | Bouton avec lien | | | |
| 6.9.8 | Bloc Widget | Widget dynamique | | | |
| 6.9.9 | Bloc Row | Mise en page grille | | | |
| 6.9.10 | Réordonner blocs | Drag & drop | | | |
| 6.9.11 | Dupliquer bloc | Copie créée | | | |
| 6.9.12 | Supprimer bloc | Bloc supprimé | | | |

### 6.10 Gestion des galeries

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.10.1 | Liste galeries | Toutes les galeries | | | |
| 6.10.2 | Créer galerie | Galerie créée | | | |
| 6.10.3 | Upload images | Multiple upload | | | |
| 6.10.4 | Drag & drop | Upload par glisser-déposer | | | |
| 6.10.5 | Alt text | Modifiable par image | | | |
| 6.10.6 | Caption | Modifiable par image | | | |
| 6.10.7 | Réordonner images | Ordre modifiable | | | |
| 6.10.8 | Supprimer image | Image supprimée | | | |
| 6.10.9 | Image couverture | Sélectionnable | | | |
| 6.10.10 | Galerie privée | Non visible publiquement | | | |

### 6.11 Gestion des menus

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.11.1 | Liste menus | Menus par emplacement | | | |
| 6.11.2 | Créer menu | Menu créé | | | |
| 6.11.3 | Ajouter item (route) | Lien vers route Symfony | | | |
| 6.11.4 | Ajouter item (page) | Lien vers page CMS | | | |
| 6.11.5 | Ajouter item (URL) | URL personnalisée | | | |
| 6.11.6 | Sous-menu | Hiérarchie parent/enfant | | | |
| 6.11.7 | Icône | Icône affichée | | | |
| 6.11.8 | Ouvrir nouvel onglet | Target blank | | | |
| 6.11.9 | Réordonner items | Drag & drop | | | |
| 6.11.10 | Activer/désactiver | Toggle visible | | | |

### 6.12 Configuration du site

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.12.1 | Accès configuration | Formulaire affiché | | | |
| 6.12.2 | Nom du club | Enregistré | | | |
| 6.12.3 | Adresse | Enregistrée | | | |
| 6.12.4 | Téléphone | Enregistré | | | |
| 6.12.5 | Email | Enregistré | | | |
| 6.12.6 | URL Facebook | Enregistrée | | | |
| 6.12.7 | URL HelloAsso | Enregistrée | | | |
| 6.12.8 | PDF tarifs | Upload fonctionnel | | | |

### 6.13 Gestion des modules

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 6.13.1 | Accès (ROLE_SUPER_ADMIN) | Liste modules | | | |
| 6.13.2 | Désactiver blog | Blog inaccessible | | | |
| 6.13.3 | Désactiver galeries | Galeries inaccessibles | | | |
| 6.13.4 | Réactiver module | Module accessible | | | |
| 6.13.5 | Menu admin adapté | Liens cachés si désactivé | | | |

---

## 7. Espace Directeur de Plongée

### 7.1 Vérification CACI

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 7.1.1 | Accès `/dp/caci` | Liste utilisateurs CACI | | | |
| 7.1.2 | Statistiques | Total, à vérifier, expirés | | | |
| 7.1.3 | Liste "À vérifier" | CACI déclarés non vérifiés | | | |
| 7.1.4 | Liste "Expirés" | CACI expirés | | | |
| 7.1.5 | Vérifier CACI individuel | Statut → vérifié | | | |
| 7.1.6 | Vérification batch | Plusieurs en une fois | | | |
| 7.1.7 | Réinitialiser CACI | Vérification effacée | | | |
| 7.1.8 | Infos vérification | Date + nom DP enregistrés | | | |

### 7.2 Gestion des cotisations

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 7.2.1 | Accès `/dp/cotisations` | Page cotisations | | | |
| 7.2.2 | Saison courante | Format "2024-2025" affiché | | | |
| 7.2.3 | Statistiques | Total, payés, non payés, expirés | | | |
| 7.2.4 | Liste non payés | Membres sans cotisation | | | |
| 7.2.5 | Enregistrer cotisation | Formulaire individuel | | | |
| 7.2.6 | Montant | Montant enregistré | | | |
| 7.2.7 | Mode paiement | Espèces/chèque/virement/CB | | | |
| 7.2.8 | Enregistrement batch | Plusieurs en une fois | | | |
| 7.2.9 | Annuler cotisation | Cotisation effacée | | | |
| 7.2.10 | Liste payés | Membres à jour | | | |
| 7.2.11 | Infos cotisation | Date, montant, mode, valideur | | | |

### 7.3 Gestion des événements (DP)

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 7.3.1 | Liste événements DP | Événements à venir | | | |
| 7.3.2 | Créer événement | Création simplifiée | | | |
| 7.3.3 | Modifier événement | Modifications sauvées | | | |
| 7.3.4 | Liste participants | Tous les inscrits | | | |
| 7.3.5 | Ajouter participant | Inscription manuelle | | | |
| 7.3.6 | Retirer participant | Désinscription + promotion | | | |
| 7.3.7 | Point RDV participant | Modifiable | | | |
| 7.3.8 | Export CSV | Fichier téléchargé | | | |
| 7.3.9 | Contenu CSV | Nom, email, tél, niveau, CACI | | | |
| 7.3.10 | Encodage CSV | UTF-8 avec BOM | | | |

---

## 8. Notifications push

### 8.1 Souscription

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 8.1.1 | Navigateur Chrome | Notifications fonctionnelles | | | |
| 8.1.2 | Navigateur Firefox | Notifications fonctionnelles | | | |
| 8.1.3 | Navigateur Safari | Message non supporté ou fonctionnel | | | |
| 8.1.4 | Mobile Android | Notifications fonctionnelles | | | |
| 8.1.5 | Mobile iOS | Message non supporté ou fonctionnel | | | |

### 8.2 Réception

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 8.2.1 | Nouvelle inscription | DP notifié | | | |
| 8.2.2 | Désinscription | DP notifié | | | |
| 8.2.3 | Place libérée | Utilisateur en liste notifié | | | |
| 8.2.4 | Nouvel événement | Utilisateurs éligibles notifiés | | | |
| 8.2.5 | Clic notification | Redirection vers page concernée | | | |

---

## 9. API et intégrations

### 9.1 API Calendrier

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 9.1.1 | GET `/calendrier/api/events` | JSON événements | | | |
| 9.1.2 | Filtre mois/année | Événements filtrés | | | |
| 9.1.3 | Structure JSON | ID, titre, dates, participants | | | |

### 9.2 API Blocs

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 9.2.1 | GET blocs article | Liste JSON | | | |
| 9.2.2 | POST créer bloc | Bloc créé | | | |
| 9.2.3 | PUT modifier bloc | Bloc modifié | | | |
| 9.2.4 | DELETE bloc | Bloc supprimé | | | |
| 9.2.5 | POST réordonner | Ordre mis à jour | | | |

### 9.3 API DP

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 9.3.1 | GET `/dp/api/users` | Liste utilisateurs JSON | | | |
| 9.3.2 | Sans ROLE_DP | Accès refusé | | | |

---

## 10. Tests de performance

### 10.1 Temps de chargement

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 10.1.1 | Page d'accueil | < 2 secondes | | | |
| 10.1.2 | Liste blog | < 2 secondes | | | |
| 10.1.3 | Calendrier | < 2 secondes | | | |
| 10.1.4 | Dashboard admin | < 3 secondes | | | |
| 10.1.5 | Liste utilisateurs | < 3 secondes | | | |

### 10.2 Optimisation images

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 10.2.1 | Images WebP | Format WebP utilisé | | | |
| 10.2.2 | Thumbnails | Générés automatiquement | | | |
| 10.2.3 | Lazy loading | Images chargées au scroll | | | |

---

## 11. Tests de sécurité

### 11.1 Authentification

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 11.1.1 | Brute force login | Rate limiting ou captcha | | | |
| 11.1.2 | Session expirée | Redirection login | | | |
| 11.1.3 | CSRF tokens | Présents sur tous formulaires | | | |
| 11.1.4 | Token CSRF invalide | Rejet formulaire | | | |

### 11.2 Autorisation

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 11.2.1 | Admin sans ROLE_ADMIN | 403 Forbidden | | | |
| 11.2.2 | DP sans ROLE_DP | 403 Forbidden | | | |
| 11.2.3 | Super admin sans rôle | 403 Forbidden | | | |
| 11.2.4 | Modifier autre profil | Interdit | | | |

### 11.3 Upload fichiers

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 11.3.1 | Fichier .php | Rejeté | | | |
| 11.3.2 | Fichier .exe | Rejeté | | | |
| 11.3.3 | Double extension .jpg.php | Rejeté | | | |
| 11.3.4 | MIME type falsifié | Rejeté | | | |
| 11.3.5 | Fichier > limite | Rejeté avec message | | | |

### 11.4 Injection

| # | Test | Attendu | OK | KO | Remarques |
|---|------|---------|----|----|-----------|
| 11.4.1 | XSS dans formulaires | HTML échappé | | | |
| 11.4.2 | SQL injection | Requêtes paramétrées | | | |
| 11.4.3 | Script dans contenu | Sanitization | | | |

---

## Légende

- **OK** : Test réussi
- **KO** : Test échoué
- **N/A** : Non applicable
- **Remarques** : Notes, bugs trouvés, suggestions

---

## Signatures

| Rôle | Nom | Date | Signature |
|------|-----|------|-----------|
| Testeur | | | |
| Développeur | | | |
| Product Owner | | | |

---

## Historique des versions

| Version | Date | Auteur | Modifications |
|---------|------|--------|---------------|
| 1.0 | 09/12/2025 | Claude Code | Création initiale |
