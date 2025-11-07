# Cahier de Recette

[⬅️ Retour à l'index](README.md)

## 📋 Informations Générales

**Application :** Système de gestion Club Subaquatique des Vénètes
**Version :** 1.0
**Date :** 2025-11-06
**Testeur :** _____________________

---

## 🎯 Objectif des Tests

Ce cahier de recette permet de valider l'ensemble des fonctionnalités de l'application avant mise en production ou après modifications majeures.

### Niveaux de Test

- ✅ **OK** : Fonctionnalité conforme
- ⚠️ **KO** : Fonctionnalité non conforme
- 🔶 **Partiel** : Fonctionnalité partiellement conforme
- ⏭️ **N/A** : Non applicable

---

## 👤 Comptes de Test Requis

Créer les comptes suivants avant de commencer les tests :

| Rôle | Email | Mot de passe | Niveau Plongée |
|------|-------|--------------|----------------|
| Visiteur | - | - | - |
| Membre | membre@test.fr | Test123! | PA20 |
| Membre 2 | membre2@test.fr | Test123! | PE12 |
| DP | dp@test.fr | Test123! | N4 |
| Admin | admin@test.fr | Test123! | MF1 |
| Super Admin | superadmin@test.fr | Test123! | MF2 |

---

## 🌐 MODULE PUBLIC (Sans Authentification)

### TEST-PUB-001 : Page d'Accueil

**Objectif :** Vérifier l'affichage de la page d'accueil

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/` | Page s'affiche sans erreur | ☐ |
| 2 | Vérifier logo/titre club | Logo et titre visibles | ☐ |
| 3 | Vérifier navigation | Liens menu fonctionnels | ☐ |
| 4 | Vérifier responsive | S'adapte mobile/tablet/desktop | ☐ |

**Notes :** _____________________

---

### TEST-PUB-002 : Calendrier Public

**Objectif :** Consulter le calendrier des événements

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/calendrier` | Calendrier mensuel s'affiche | ☐ |
| 2 | Vérifier événements affichés | Événements visibles avec couleurs types | ☐ |
| 3 | Cliquer "Mois suivant" | Navigation vers mois suivant | ☐ |
| 4 | Cliquer "Mois précédent" | Navigation vers mois précédent | ☐ |
| 5 | Cliquer sur un événement | Redirection vers détails événement | ☐ |

**Notes :** _____________________

---

### TEST-PUB-003 : Détails Événement Public

**Objectif :** Consulter les détails d'un événement

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Depuis calendrier, cliquer événement | Page détails s'affiche | ☐ |
| 2 | Vérifier informations | Titre, date, lieu, description visibles | ☐ |
| 3 | Vérifier type événement | Type et couleur affichés | ☐ |
| 4 | Vérifier places | "X places disponibles / Y" affiché | ☐ |
| 5 | Vérifier niveau requis | Niveau minimum affiché si défini | ☐ |
| 6 | Vérifier bouton inscription | "Connectez-vous pour vous inscrire" visible | ☐ |
| 7 | Vérifier liste participants | Non visible (réservé admin/DP) | ☐ |

**Notes :** _____________________

---

### TEST-PUB-004 : Blog - Liste Articles

**Objectif :** Consulter les articles du blog

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/blog` | Liste articles s'affiche | ☐ |
| 2 | Vérifier articles | Titre, extrait, image visible | ☐ |
| 3 | Vérifier auteur/date | Auteur et date publication visibles | ☐ |
| 4 | Vérifier catégorie/tags | Catégorie et tags affichés | ☐ |
| 5 | Cliquer sur article | Redirection vers article complet | ☐ |
| 6 | Filtrer par catégorie | Seuls articles de cette catégorie | ☐ |
| 7 | Filtrer par tag | Seuls articles avec ce tag | ☐ |
| 8 | Vérifier pagination | Navigation entre pages fonctionne | ☐ |

**Notes :** _____________________

---

### TEST-PUB-005 : Blog - Article Complet

**Objectif :** Lire un article complet

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Depuis liste, cliquer article | Article complet s'affiche | ☐ |
| 2 | Vérifier titre | Titre affiché | ☐ |
| 3 | Vérifier image à la une | Image visible si définie | ☐ |
| 4 | Vérifier contenu | Contenu formaté correctement (HTML) | ☐ |
| 5 | Vérifier auteur/date | Informations auteur visibles | ☐ |
| 6 | Vérifier articles similaires | Suggestions d'articles affichées | ☐ |

**Notes :** _____________________

---

### TEST-PUB-006 : Galeries Photos

**Objectif :** Consulter les galeries publiques

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/galleries` | Liste galeries s'affiche | ☐ |
| 2 | Vérifier galeries publiques | Seules galeries publiques visibles | ☐ |
| 3 | Cliquer sur galerie | Ouverture galerie | ☐ |
| 4 | Vérifier images | Images affichées en grille | ☐ |
| 5 | Cliquer sur image | Lightbox/zoom s'ouvre | ☐ |
| 6 | Navigation lightbox | Flèches précédent/suivant | ☐ |
| 7 | Fermer lightbox | Retour à la grille | ☐ |

**Notes :** _____________________

---

### TEST-PUB-007 : Pages Statiques

**Objectif :** Consulter les pages d'information

| Page | URL | Contenu Visible | Statut |
|------|-----|----------------|--------|
| Qui sommes-nous | `/qui-sommes-nous` | ☐ | ☐ |
| Où nous trouver | `/ou-nous-trouver` | ☐ | ☐ |
| Tarifs 2025 | `/tarifs-2025` | ☐ | ☐ |
| Nos partenaires | `/nos-partenaires` | ☐ | ☐ |
| Nos activités | `/nos-activites` | ☐ | ☐ |

**Notes :** _____________________

---

### TEST-PUB-008 : Inscription Nouveau Membre

**Objectif :** Créer un nouveau compte membre

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/register` | Formulaire inscription s'affiche | ☐ |
| 2 | Remplir formulaire valide | Tous champs acceptés | ☐ |
| 3 | Soumettre formulaire | Message "Inscription réussie" | ☐ |
| 4 | Vérifier email | Email de vérification reçu | ☐ |
| 5 | Cliquer lien email | Email marqué comme vérifié | ☐ |
| 6 | Tenter connexion | Compte en attente approbation | ☐ |

**Cas d'erreur à tester :**

| Cas | Action | Résultat Attendu | Statut |
|-----|--------|------------------|--------|
| Email existant | Utiliser email déjà inscrit | Erreur "Email déjà utilisé" | ☐ |
| Mot de passe faible | Utiliser "123" | Erreur "Minimum 8 caractères" | ☐ |
| Champs vides | Soumettre formulaire vide | Erreurs sur champs requis | ☐ |

**Notes :** _____________________

---

### TEST-PUB-009 : Connexion

**Objectif :** Se connecter à l'application

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/login` | Formulaire connexion s'affiche | ☐ |
| 2 | Entrer identifiants valides | Connexion réussie | ☐ |
| 3 | Vérifier redirection | Redirection vers page appropriée | ☐ |
| 4 | Vérifier menu user | Nom utilisateur et lien profil visibles | ☐ |

**Cas d'erreur à tester :**

| Cas | Action | Résultat Attendu | Statut |
|-----|--------|------------------|--------|
| Identifiants invalides | Mauvais mot de passe | Erreur "Identifiants invalides" | ☐ |
| Compte non approuvé | Compte pending | Erreur "Compte en attente" | ☐ |
| Email non vérifié | Email non vérifié | Erreur "Email non vérifié" | ☐ |
| Compte inactif | Compte désactivé | Erreur "Compte inactif" | ☐ |

**Notes :** _____________________

---

## 👤 MODULE MEMBRE (ROLE_USER)

**Prérequis :** Se connecter avec `membre@test.fr`

### TEST-MEM-001 : Profil Utilisateur

**Objectif :** Consulter et modifier son profil

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/profile` | Page profil s'affiche | ☐ |
| 2 | Vérifier informations | Nom, prénom, email, niveau visibles | ☐ |
| 3 | Vérifier événements | Liste événements inscrits visible | ☐ |
| 4 | Cliquer "Modifier profil" | Formulaire édition s'affiche | ☐ |
| 5 | Modifier nom | Modification enregistrée | ☐ |
| 6 | Vérifier mise à jour | Nouveau nom affiché | ☐ |

**Notes :** _____________________

---

### TEST-MEM-002 : Inscription à Événement (Cas Normal)

**Objectif :** S'inscrire à un événement avec places disponibles

**Prérequis :** Créer événement avec 10 places, niveau PA20 min

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à événement | Bouton "S'inscrire" visible | ☐ |
| 2 | Cliquer "S'inscrire" | Modal choix point RDV s'affiche | ☐ |
| 3 | Choisir "RDV Club" | Choix enregistré | ☐ |
| 4 | Valider inscription | Message "Inscription confirmée" | ☐ |
| 5 | Vérifier statut | Statut "Confirmé" affiché | ☐ |
| 6 | Vérifier places | Compteur places mis à jour (9/10) | ☐ |
| 7 | Vérifier profil | Événement dans "Mes inscriptions" | ☐ |

**Notes :** _____________________

---

### TEST-MEM-003 : Inscription à Événement (Niveau Insuffisant)

**Objectif :** Vérifier refus si niveau insuffisant

**Prérequis :**
- Se connecter avec `membre2@test.fr` (PE12)
- Créer événement niveau PA40 minimum

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à événement | Bouton "S'inscrire" désactivé ou absent | ☐ |
| 2 | Vérifier message | "Niveau PA40 minimum requis" affiché | ☐ |
| 3 | Tenter inscription (si possible) | Erreur "Niveau insuffisant" | ☐ |

**Notes :** _____________________

---

### TEST-MEM-004 : Liste d'Attente

**Objectif :** Vérifier gestion liste d'attente

**Prérequis :** Créer événement avec 2 places max

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Membre 1 s'inscrit | Statut "Confirmé", places 1/2 | ☐ |
| 2 | Membre 2 s'inscrit | Statut "Confirmé", places 2/2 | ☐ |
| 3 | Membre 3 s'inscrit | Statut "Liste d'attente" | ☐ |
| 4 | Vérifier message | "Événement complet, liste d'attente" | ☐ |
| 5 | Membre 1 se désinscrit | Places 1/2 | ☐ |
| 6 | Vérifier Membre 3 | Automatiquement promu "Confirmé" | ☐ |

**Notes :** _____________________

---

### TEST-MEM-005 : Désinscription

**Objectif :** Se désinscrire d'un événement

**Prérequis :** Être inscrit à un événement

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Depuis profil, voir inscriptions | Liste événements affichée | ☐ |
| 2 | Cliquer "Se désinscrire" | Modal confirmation s'affiche | ☐ |
| 3 | Confirmer désinscription | Message "Désinscription réussie" | ☐ |
| 4 | Vérifier liste | Événement retiré de la liste | ☐ |
| 5 | Vérifier places | Compteur places mis à jour | ☐ |

**Notes :** _____________________

---

### TEST-MEM-006 : Conditions d'Éligibilité Personnalisées

**Objectif :** Vérifier respect des conditions custom

**Prérequis :**
- Admin a créé condition "certificat médical valide"
- Membre n'a pas de certificat

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à événement avec condition | Message condition visible | ☐ |
| 2 | Tenter inscription | Erreur "Certificat médical requis" | ☐ |
| 3 | Ne pas pouvoir s'inscrire | Inscription bloquée | ☐ |

**Notes :** _____________________

---

## 🏊 MODULE DIRECTEUR DE PLONGÉE (ROLE_DP)

**Prérequis :** Se connecter avec `dp@test.fr`

### TEST-DP-001 : Accès Interface DP

**Objectif :** Vérifier accès à l'interface DP

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Vérifier menu | Lien "Interface DP" visible | ☐ |
| 2 | Accéder à `/dp/events` | Liste événements plongée s'affiche | ☐ |
| 3 | Vérifier filtrage | Seuls événements type "plongée" | ☐ |

**Notes :** _____________________

---

### TEST-DP-002 : Vue Participants par Niveau

**Objectif :** Consulter participants groupés par niveau

**Prérequis :**
- Événement avec 5 participants de niveaux différents
- 2× PA40, 2× PA20, 1× N4

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ouvrir événement | Détails événement s'affichent | ☐ |
| 2 | Vérifier groupement | Participants groupés par niveau | ☐ |
| 3 | Vérifier section N4 | 1 participant, nom visible | ☐ |
| 4 | Vérifier section PA40 | 2 participants, noms visibles | ☐ |
| 5 | Vérifier section PA20 | 2 participants, noms visibles | ☐ |
| 6 | Vérifier RDV | Point RDV affiché pour chaque participant | ☐ |

**Notes :** _____________________

---

### TEST-DP-003 : Gestion Participants

**Objectif :** Gérer les participants d'un événement

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ajouter note participant | Note enregistrée et visible | ☐ |
| 2 | Modifier note | Modification enregistrée | ☐ |
| 3 | Voir historique participant | Participations passées visibles | ☐ |

**Notes :** _____________________

---

## ⚙️ MODULE ADMINISTRATION (ROLE_ADMIN)

**Prérequis :** Se connecter avec `admin@test.fr`

### TEST-ADM-001 : Dashboard Admin

**Objectif :** Vérifier le tableau de bord

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/admin` | Dashboard s'affiche | ☐ |
| 2 | Vérifier statistiques | Chiffres clés visibles | ☐ |
| 3 | Vérifier navigation | Menu latéral visible | ☐ |
| 4 | Vérifier liens rapides | Liens vers modules principaux | ☐ |

**Notes :** _____________________

---

### TEST-ADM-002 : Gestion Événements - Création Simple

**Objectif :** Créer un événement simple (non récurrent)

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/admin/events` | Liste événements s'affiche | ☐ |
| 2 | Cliquer "Nouvel événement" | Formulaire s'affiche | ☐ |
| 3 | Remplir titre | "Sortie Épave Test" | ☐ |
| 4 | Remplir description | Texte enrichi fonctionne | ☐ |
| 5 | Choisir type | "Sortie Plongée" | ☐ |
| 6 | Définir date/heure | Date et heure enregistrées | ☐ |
| 7 | Définir lieu | "Port de Vannes" | ☐ |
| 8 | Définir capacité | 12 participants max | ☐ |
| 9 | Définir niveau minimum | PA20 | ☐ |
| 10 | Définir RDV club | 8h30 | ☐ |
| 11 | Définir RDV site | 9h30 | ☐ |
| 12 | Soumettre formulaire | Message "Événement créé" | ☐ |
| 13 | Vérifier liste | Événement visible dans liste | ☐ |
| 14 | Vérifier calendrier public | Événement visible sur calendrier | ☐ |

**Notes :** _____________________

---

### TEST-ADM-003 : Gestion Événements - Récurrence Hebdomadaire

**Objectif :** Créer événement récurrent hebdomadaire

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Créer nouvel événement | Formulaire s'affiche | ☐ |
| 2 | Cocher "Événement récurrent" | Options récurrence apparaissent | ☐ |
| 3 | Choisir type "Hebdomadaire" | Champ jours apparaît | ☐ |
| 4 | Cocher "Lundi, Mercredi, Vendredi" | Sélection enregistrée | ☐ |
| 5 | Définir date fin | Dans 4 semaines | ☐ |
| 6 | Soumettre formulaire | Message "12 événements créés" | ☐ |
| 7 | Vérifier liste | 12 événements visibles | ☐ |
| 8 | Vérifier calendrier | Événements sur Lun/Mer/Ven uniquement | ☐ |
| 9 | Vérifier lien parent | Événements liés au parent | ☐ |

**Notes :** _____________________

---

### TEST-ADM-004 : Gestion Événements - Récurrence Mensuelle

**Objectif :** Créer événement récurrent mensuel

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Créer événement le 15 du mois | Date 15/01 | ☐ |
| 2 | Cocher récurrent "Mensuel" | Options mensuelles | ☐ |
| 3 | Date fin dans 6 mois | 15/07 | ☐ |
| 4 | Soumettre | 6 événements créés | ☐ |
| 5 | Vérifier dates | Le 15 de chaque mois | ☐ |

**Notes :** _____________________

---

### TEST-ADM-005 : Gestion Événements - Modification

**Objectif :** Modifier un événement existant

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ouvrir événement | Détails s'affichent | ☐ |
| 2 | Cliquer "Modifier" | Formulaire pré-rempli | ☐ |
| 3 | Modifier titre | Nouveau titre enregistré | ☐ |
| 4 | Modifier capacité | Nouvelle capacité enregistrée | ☐ |
| 5 | Soumettre | Message "Modifications enregistrées" | ☐ |
| 6 | Vérifier changements | Modifications visibles | ☐ |

**Cas événement récurrent :**

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Modifier événement parent | Options affichées | ☐ |
| 2 | Choisir "Modifier série complète" | Confirmation demandée | ☐ |
| 3 | Confirmer | Tous événements fils modifiés | ☐ |

**Notes :** _____________________

---

### TEST-ADM-006 : Gestion Événements - Suppression

**Objectif :** Supprimer un événement

**Cas simple (sans participants) :**

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ouvrir événement | Détails s'affichent | ☐ |
| 2 | Cliquer "Supprimer" | Modal confirmation s'affiche | ☐ |
| 3 | Confirmer suppression | Événement supprimé | ☐ |
| 4 | Vérifier liste | Événement absent | ☐ |

**Cas avec participants :**

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Événement avec 3 inscrits | - | ☐ |
| 2 | Cliquer "Supprimer" | Avertissement "3 participants inscrits" | ☐ |
| 3 | Confirmer | Suppressions participations + événement | ☐ |

**Cas série récurrente :**

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Supprimer événement parent | Options "série complète" ou "à partir de" | ☐ |
| 2 | Choisir "À partir du 15/02" | Confirmation | ☐ |
| 3 | Confirmer | Événements à partir 15/02 supprimés | ☐ |
| 4 | Vérifier | Événements avant 15/02 conservés | ☐ |

**Notes :** _____________________

---

### TEST-ADM-007 : Types d'Événements

**Objectif :** Gérer les types d'événements

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/admin/event-types` | Liste types s'affiche | ☐ |
| 2 | Cliquer "Nouveau type" | Formulaire s'affiche | ☐ |
| 3 | Nom "Formation Niveau 1" | Enregistré | ☐ |
| 4 | Code "formation-n1" | Enregistré | ☐ |
| 5 | Couleur "#10B981" (vert) | Sélecteur couleur fonctionne | ☐ |
| 6 | Soumettre | Type créé | ☐ |
| 7 | Vérifier liste | Type visible avec couleur | ☐ |
| 8 | Modifier type | Modifications enregistrées | ☐ |
| 9 | Désactiver type | Type masqué des formulaires | ☐ |
| 10 | Supprimer type | Vérifier événements liés | ☐ |

**Notes :** _____________________

---

### TEST-ADM-008 : Conditions d'Éligibilité

**Objectif :** Créer conditions personnalisées

**Prérequis :** Créer un événement test

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ouvrir événement | Détails affichés | ☐ |
| 2 | Cliquer "Gérer conditions" | Liste conditions (vide) | ☐ |
| 3 | Cliquer "Nouvelle condition" | Formulaire s'affiche | ☐ |
| 4 | Choisir entité "User" | Sélecteur attributs s'affiche | ☐ |
| 5 | Choisir attribut "highestDivingLevel.sortOrder" | Attribut sélectionné | ☐ |
| 6 | Opérateur ">=" | Sélectionné | ☐ |
| 7 | Valeur "40" | Saisie | ☐ |
| 8 | Message erreur "Niveau PA40 minimum" | Saisi | ☐ |
| 9 | Soumettre | Condition créée | ☐ |
| 10 | Tester avec membre PA20 | Inscription refusée | ☐ |
| 11 | Tester avec membre PA40 | Inscription acceptée | ☐ |

**Cas multiples conditions :**

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ajouter 2ème condition "email vérifié" | Condition créée | ☐ |
| 2 | Tester avec PA40 mais email non vérifié | Refusé | ☐ |
| 3 | Vérifier message | "Email non vérifié" affiché | ☐ |

**Notes :** _____________________

---

### TEST-ADM-009 : Gestion Utilisateurs - Liste

**Objectif :** Consulter et filtrer les utilisateurs

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/admin/users` | Liste utilisateurs s'affiche | ☐ |
| 2 | Vérifier colonnes | Nom, email, niveau, statut, rôles | ☐ |
| 3 | Filtrer statut "pending" | Seuls comptes en attente | ☐ |
| 4 | Filtrer statut "approved" | Seuls comptes approuvés | ☐ |
| 5 | Filtrer rôle "ROLE_DP" | Seuls DPs affichés | ☐ |
| 6 | Rechercher par email | Résultats filtrés | ☐ |
| 7 | Rechercher par nom | Résultats filtrés | ☐ |

**Notes :** _____________________

---

### TEST-ADM-010 : Gestion Utilisateurs - Approbation

**Objectif :** Approuver/rejeter nouveaux comptes

**Prérequis :** Avoir un compte en statut "pending"

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ouvrir utilisateur pending | Détails s'affichent | ☐ |
| 2 | Boutons "Approuver" et "Rejeter" | Visibles | ☐ |
| 3 | Cliquer "Approuver" | Modal confirmation | ☐ |
| 4 | Confirmer | Message "Compte approuvé" | ☐ |
| 5 | Vérifier statut | Statut = "approved", active = true | ☐ |
| 6 | User tente connexion | Connexion réussie | ☐ |

**Cas rejet :**

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ouvrir user pending | Détails s'affichent | ☐ |
| 2 | Cliquer "Rejeter" | Modal avec champ raison | ☐ |
| 3 | Saisir raison | Texte accepté | ☐ |
| 4 | Confirmer | Compte rejeté | ☐ |
| 5 | Vérifier statut | Statut = "rejected" | ☐ |
| 6 | User tente connexion | Erreur "Compte rejeté" | ☐ |

**Notes :** _____________________

---

### TEST-ADM-011 : Gestion Utilisateurs - Modification

**Objectif :** Modifier un utilisateur existant

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ouvrir utilisateur | Détails s'affichent | ☐ |
| 2 | Cliquer "Modifier" | Formulaire pré-rempli | ☐ |
| 3 | Modifier niveau plongée | Nouveau niveau sélectionné | ☐ |
| 4 | Ajouter rôle ROLE_DP | Case cochée | ☐ |
| 5 | Soumettre | Modifications enregistrées | ☐ |
| 6 | User se connecte | A maintenant accès interface DP | ☐ |
| 7 | Retirer rôle ROLE_DP | Case décochée | ☐ |
| 8 | Soumettre | Rôle retiré | ☐ |
| 9 | User se connecte | N'a plus accès DP | ☐ |

**Notes :** _____________________

---

### TEST-ADM-012 : Gestion Utilisateurs - Désactivation

**Objectif :** Désactiver un compte utilisateur

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ouvrir utilisateur actif | Détails s'affichent | ☐ |
| 2 | Décocher "Compte actif" | Case décochée | ☐ |
| 3 | Soumettre | Compte désactivé | ☐ |
| 4 | User tente connexion | Erreur "Compte inactif" | ☐ |
| 5 | Réactiver compte | Case cochée | ☐ |
| 6 | User se connecte | Connexion réussie | ☐ |

**Notes :** _____________________

---

### TEST-ADM-013 : Niveaux de Plongée

**Objectif :** Gérer les niveaux de plongée

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/admin/diving-levels` | Liste niveaux s'affiche | ☐ |
| 2 | Cliquer "Nouveau niveau" | Formulaire s'affiche | ☐ |
| 3 | Nom "Plongeur Autonome 60m" | Enregistré | ☐ |
| 4 | Code "PA60" | Enregistré | ☐ |
| 5 | Ordre 50 | Enregistré | ☐ |
| 6 | Description | Texte enregistré | ☐ |
| 7 | Soumettre | Niveau créé | ☐ |
| 8 | Vérifier ordre liste | Niveaux triés par sortOrder | ☐ |
| 9 | Modifier niveau | Modifications enregistrées | ☐ |
| 10 | Désactiver niveau | Masqué des sélecteurs | ☐ |

**Notes :** _____________________

---

### TEST-ADM-014 : Blog - Création Article

**Objectif :** Créer un article de blog

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/admin/articles` | Liste articles s'affiche | ☐ |
| 2 | Cliquer "Nouvel article" | Formulaire s'affiche | ☐ |
| 3 | Titre "Sortie Épave Juin 2025" | Enregistré | ☐ |
| 4 | Slug auto-généré | "sortie-epave-juin-2025" | ☐ |
| 5 | Contenu avec éditeur riche | Formatage fonctionne | ☐ |
| 6 | Upload image à la une | Image uploadée | ☐ |
| 7 | Catégorie "Sorties" | Sélectionnée | ☐ |
| 8 | Tags "épave, plongée profonde" | Enregistrés | ☐ |
| 9 | Statut "draft" | Sélectionné | ☐ |
| 10 | Soumettre | Article créé | ☐ |
| 11 | Vérifier blog public | Article non visible (draft) | ☐ |
| 12 | Passer en "published" | Article publié | ☐ |
| 13 | Vérifier blog public | Article visible | ☐ |

**Notes :** _____________________

---

### TEST-ADM-015 : Blog - Modification Article

**Objectif :** Modifier un article existant

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ouvrir article | Détails s'affichent | ☐ |
| 2 | Cliquer "Modifier" | Formulaire pré-rempli | ☐ |
| 3 | Modifier titre | Nouveau titre enregistré | ☐ |
| 4 | Modifier contenu | HTML sanitizé (scripts retirés) | ☐ |
| 5 | Ajouter tag | Nouveau tag enregistré | ☐ |
| 6 | Soumettre | Modifications enregistrées | ☐ |
| 7 | Vérifier blog public | Changements visibles | ☐ |

**Notes :** _____________________

---

### TEST-ADM-016 : Galeries - Création

**Objectif :** Créer une galerie photos

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/admin/galleries` | Liste galeries s'affiche | ☐ |
| 2 | Cliquer "Nouvelle galerie" | Formulaire s'affiche | ☐ |
| 3 | Titre "Sortie Arradon - Juin 2025" | Enregistré | ☐ |
| 4 | Slug auto-généré | "sortie-arradon-juin-2025" | ☐ |
| 5 | Description | Texte enregistré | ☐ |
| 6 | Visibilité "Public" | Sélectionnée | ☐ |
| 7 | Soumettre | Galerie créée | ☐ |

**Notes :** _____________________

---

### TEST-ADM-017 : Galeries - Upload Images

**Objectif :** Ajouter des images à une galerie

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ouvrir galerie | Détails s'affichent | ☐ |
| 2 | Cliquer "Uploader images" | Interface upload s'affiche | ☐ |
| 3 | Sélectionner 5 images | Images sélectionnées | ☐ |
| 4 | Uploader | Barre progression visible | ☐ |
| 5 | Vérifier upload | 5 images affichées | ☐ |
| 6 | Vérifier thumbnails | Miniatures générées automatiquement | ☐ |
| 7 | Ajouter légende image 1 | Légende enregistrée | ☐ |
| 8 | Réorganiser images | Drag & drop fonctionne | ☐ |
| 9 | Définir image couverture | Image définie | ☐ |
| 10 | Supprimer une image | Image supprimée | ☐ |

**Cas erreur upload :**

| Cas | Action | Résultat Attendu | Statut |
|-----|--------|------------------|--------|
| Fichier trop gros | Upload 50MB | Erreur "Taille max 10MB" | ☐ |
| Mauvais format | Upload PDF | Erreur "Format non supporté" | ☐ |

**Notes :** _____________________

---

### TEST-ADM-018 : Pages - Création

**Objectif :** Créer une page statique

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/admin/pages` | Liste pages s'affiche | ☐ |
| 2 | Cliquer "Nouvelle page" | Formulaire s'affiche | ☐ |
| 3 | Titre "Contact" | Enregistré | ☐ |
| 4 | Slug "contact" | Enregistré | ☐ |
| 5 | Contenu HTML | Contenu enregistré | ☐ |
| 6 | Meta titre SEO | Enregistré | ☐ |
| 7 | Meta description | Enregistrée | ☐ |
| 8 | Statut "published" | Sélectionné | ☐ |
| 9 | Soumettre | Page créée | ☐ |
| 10 | Vérifier génération template | `templates/pages/contact.html.twig` créé | ☐ |
| 11 | Accéder à `/contact` | Page s'affiche | ☐ |

**Notes :** _____________________

---

### TEST-ADM-019 : Configuration Site

**Objectif :** Configurer les paramètres du site

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/admin/config` | Liste configurations s'affiche | ☐ |
| 2 | Modifier "Nom du site" | Nouveau nom enregistré | ☐ |
| 3 | Modifier "Email contact" | Nouvel email enregistré | ☐ |
| 4 | Modifier "Max upload" | Nouvelle valeur enregistrée | ☐ |
| 5 | Vérifier application | Nouveau nom affiché partout | ☐ |

**Notes :** _____________________

---

## 🔧 MODULE SUPER ADMIN (ROLE_SUPER_ADMIN)

**Prérequis :** Se connecter avec `superadmin@test.fr`

### TEST-SUPER-001 : Gestion Modules

**Objectif :** Activer/désactiver modules

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Accéder à `/admin/modules` | Liste modules s'affiche | ☐ |
| 2 | Désactiver module "Blog" | Module désactivé | ☐ |
| 3 | Vérifier menu | Lien "Blog" absent du menu | ☐ |
| 4 | Tenter accéder `/blog` | Erreur 404 ou page désactivée | ☐ |
| 5 | Réactiver module | Module réactivé | ☐ |
| 6 | Vérifier menu | Lien "Blog" visible | ☐ |
| 7 | Accéder `/blog` | Page fonctionne | ☐ |

**Tester pour chaque module :**

| Module | Désactivation | Réactivation | Statut |
|--------|---------------|--------------|--------|
| Blog | ☐ | ☐ | ☐ |
| Pages | ☐ | ☐ | ☐ |
| Galeries | ☐ | ☐ | ☐ |
| Événements | ☐ | ☐ | ☐ |

**Notes :** _____________________

---

## 🔒 TESTS SÉCURITÉ

### TEST-SEC-001 : Protection CSRF

**Objectif :** Vérifier protection CSRF sur formulaires

| Étape | Action | Résultat Attendu | Statut |
|-------|--------|------------------|--------|
| 1 | Ouvrir formulaire inscription | Token CSRF présent dans HTML | ☐ |
| 2 | Supprimer token via DevTools | Token retiré | ☐ |
| 3 | Soumettre formulaire | Erreur "CSRF token invalide" | ☐ |

**Notes :** _____________________

---

### TEST-SEC-002 : Validation Entrées

**Objectif :** Vérifier validation des données

| Cas | Action | Résultat Attendu | Statut |
|-----|--------|------------------|--------|
| XSS Script | Entrer `<script>alert('XSS')</script>` | Script échappé ou sanitizé | ☐ |
| XSS Image | Entrer `<img src=x onerror=alert(1)>` | Tag nettoyé | ☐ |
| SQL Injection | Email `' OR 1=1--` | Requête échappée, pas d'effet | ☐ |

**Notes :** _____________________

---

### TEST-SEC-003 : Contrôle d'Accès

**Objectif :** Vérifier que les rôles sont respectés

| Utilisateur | URL | Résultat Attendu | Statut |
|-------------|-----|------------------|--------|
| Visiteur | `/admin` | Redirection login | ☐ |
| USER | `/admin` | Accès refusé | ☐ |
| USER | `/dp` | Accès refusé | ☐ |
| DP | `/dp` | Accès OK | ☐ |
| DP | `/admin/modules` | Accès refusé | ☐ |
| ADMIN | `/admin` | Accès OK | ☐ |
| ADMIN | `/admin/modules` | Accès refusé | ☐ |
| SUPER_ADMIN | `/admin/modules` | Accès OK | ☐ |

**Notes :** _____________________

---

### TEST-SEC-004 : Upload Fichiers

**Objectif :** Vérifier sécurité upload

| Cas | Action | Résultat Attendu | Statut |
|-----|--------|------------------|--------|
| Script PHP | Uploader malware.php | Rejeté | ☐ |
| Fichier .exe | Uploader virus.exe | Rejeté | ☐ |
| Image valide | Uploader photo.jpg | Accepté | ☐ |
| Image + script | Image avec EXIF malveillant | Nettoyé | ☐ |

**Notes :** _____________________

---

## 📱 TESTS RESPONSIVE

### TEST-RESP-001 : Navigation Mobile

**Objectif :** Vérifier l'expérience mobile

| Résolution | Action | Résultat Attendu | Statut |
|------------|--------|------------------|--------|
| 375x667 (iPhone SE) | Naviguer site | Lisible, utilisable | ☐ |
| 768x1024 (iPad) | Naviguer site | Lisible, utilisable | ☐ |
| 1920x1080 (Desktop) | Naviguer site | Lisible, utilisable | ☐ |

**Éléments à tester :**

| Élément | Mobile | Tablet | Desktop | Statut |
|---------|--------|--------|---------|--------|
| Menu navigation | Burger menu | ☐ | Menu complet | ☐ |
| Calendrier | Scrollable | ☐ | Grille | ☐ |
| Formulaires | Champs empilés | ☐ | Inline possible | ☐ |
| Galerie | 1 colonne | ☐ | 3-4 colonnes | ☐ |

**Notes :** _____________________

---

## ⚡ TESTS PERFORMANCE

### TEST-PERF-001 : Temps de Chargement

**Objectif :** Vérifier performances de chargement

| Page | Temps Cible | Temps Réel | Statut |
|------|-------------|------------|--------|
| Accueil | < 2s | _____ | ☐ |
| Calendrier | < 3s | _____ | ☐ |
| Liste blog | < 2s | _____ | ☐ |
| Admin dashboard | < 3s | _____ | ☐ |

**Outil :** Chrome DevTools Network tab (throttling Fast 3G)

**Notes :** _____________________

---

## 🌐 TESTS COMPATIBILITÉ NAVIGATEURS

### TEST-COMP-001 : Navigateurs

**Objectif :** Vérifier compatibilité multi-navigateurs

| Navigateur | Version | Fonctionnalités OK | Statut |
|------------|---------|-------------------|--------|
| Chrome | Dernière | ☐ | ☐ |
| Firefox | Dernière | ☐ | ☐ |
| Safari | Dernière | ☐ | ☐ |
| Edge | Dernière | ☐ | ☐ |

**Notes :** _____________________

---

## 📧 TESTS EMAIL (Si implémenté)

### TEST-EMAIL-001 : Emails Transactionnels

**Objectif :** Vérifier envoi emails

| Email | Trigger | Reçu | Contenu OK | Statut |
|-------|---------|------|------------|--------|
| Vérification email | Inscription | ☐ | ☐ | ☐ |
| Compte approuvé | Approbation admin | ☐ | ☐ | ☐ |
| Compte rejeté | Rejet admin | ☐ | ☐ | ☐ |
| Confirmation inscription | Inscription événement | ☐ | ☐ | ☐ |
| Liste d'attente | Inscription complet | ☐ | ☐ | ☐ |
| Promotion | Place libérée | ☐ | ☐ | ☐ |
| Rappel événement | 48h avant événement | ☐ | ☐ | ☐ |

**Notes :** _____________________

---

## ✅ SYNTHÈSE DES TESTS

### Statistiques

| Catégorie | Total Tests | OK | KO | Partiel | N/A |
|-----------|-------------|----|----|---------|-----|
| Public | ___ | ___ | ___ | ___ | ___ |
| Membre | ___ | ___ | ___ | ___ | ___ |
| DP | ___ | ___ | ___ | ___ | ___ |
| Admin | ___ | ___ | ___ | ___ | ___ |
| Super Admin | ___ | ___ | ___ | ___ | ___ |
| Sécurité | ___ | ___ | ___ | ___ | ___ |
| Responsive | ___ | ___ | ___ | ___ | ___ |
| Performance | ___ | ___ | ___ | ___ | ___ |
| **TOTAL** | ___ | ___ | ___ | ___ | ___ |

### Anomalies Bloquantes

| ID | Description | Priorité | Assigné |
|----|-------------|----------|---------|
|    |             |          |         |

### Anomalies Non-Bloquantes

| ID | Description | Priorité | Assigné |
|----|-------------|----------|---------|
|    |             |          |         |

---

## 📝 Validation Finale

**Testé par :** _____________________

**Date :** _____________________

**Signature :** _____________________

**Commentaires :**
_____________________
_____________________
_____________________

**Validation pour mise en production :** ☐ OUI  ☐ NON

**Conditions :** _____________________

---

[⬅️ Retour à l'index](README.md)
