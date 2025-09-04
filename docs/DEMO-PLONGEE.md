# 🤿 Guide de Démo - Site de Club de Plongée

Ce guide vous aide à créer une démo convaincante d'un site de club de plongée basé sur ZeTilt CMS.

## 🎯 Objectifs de la Démo

Montrer comment ZeTilt CMS s'adapte parfaitement aux besoins d'un **club de plongée sous-marine** avec :
- Gestion des membres et niveaux de plongée
- Planning des sorties et formations
- Galeries photos privées des sorties
- Système de réservation et paiement
- Interface d'administration intuitive

## 🚀 Scénario de Démo Recommandé

### 1. Introduction (2 minutes)

**"Bonjour, je vais vous présenter ZeTilt CMS à travers l'exemple concret d'un club de plongée."**

- Expliquer le contexte : club de plongée avec 150 membres
- Besoins spécifiques : niveaux, certifications, sorties, photos
- Défis techniques : gestion des attributs dynamiques, multilingue

### 2. Interface Publique (5 minutes)

#### A. Page d'Accueil
- **Design adapté** : couleurs océan, photos de plongée
- **Slider** avec photos des dernières sorties  
- **Prochaines sorties** en évidence
- **Actualités** du club
- **Témoignages** de membres

#### B. Pages de Contenu
- **Le Club** : histoire, équipe, valeurs
- **Formations** : différents niveaux (N1, N2, N3, etc.)
- **Sites de Plongée** : carte interactive, descriptions
- **Galerie** : albums par sortie avec codes d'accès

#### C. Fonctionnalités Membres
- **Inscription** aux sorties avec niveau requis
- **Espace membre** avec ses certifications
- **Historique** des plongées
- **Photos privées** des sorties

### 3. Interface Admin (10 minutes)

#### A. Dashboard
```
Montrer le tableau de bord avec :
- 45 membres actifs
- 12 sorties prévues ce mois  
- 8 formations en cours
- 150 photos uploadées cette semaine
```

#### B. Gestion des Membres
```
Démontrer :
- Liste des plongeurs avec niveaux
- Attributs dynamiques (certificat médical, nb plongées)
- Système de rôles (Plongeur → Pilote → Directeur de Plongée)
- Notifications automatiques (certificats expirés)
```

#### C. Planning des Events
```
Montrer :
- Création d'une sortie plongée
- Paramètres : site, profondeur, niveau requis, prix
- Gestion des inscriptions et listes d'attente
- Export des listes pour les sorties
```

#### D. Galeries Photos
```
Démontrer :
- Upload par lots des photos de sortie
- Génération automatique de codes d'accès
- Commande d'impressions via Prodigi
- Partage sécurisé avec les participants
```

## 📊 Données de Démo à Préparer

### Utilisateurs Types

```sql
-- Directeur de Plongée
Nom: Jean Cousteau
Email: directeur@club-plongee.fr  
Rôle: ROLE_DIRECTEUR_PLONGEE
Niveau: MF2
Plongées: 1250
Spécialités: Nitrox, Épave, Profonde

-- Pilote de Palanquée  
Nom: Marine Leclerc
Email: marine@club-plongee.fr
Rôle: ROLE_PILOTE  
Niveau: Niveau 4
Plongées: 387
Spécialités: Biologie, Photo

-- Plongeur Débutant
Nom: Pierre Martin
Email: pierre@email.fr
Rôle: ROLE_PLONGEUR
Niveau: Niveau 1
Plongées: 12
Certificat: Valide jusqu'au 15/03/2025
```

### Événements/Sorties

```yaml
Sortie 1:
  Nom: "Plongée Épave du Sirius"
  Date: "2025-01-15 09:00"  
  Site: "Port-Cros, Var"
  Profondeur: "35m"
  Niveau requis: "Niveau 2 minimum"
  Prix membre: "45€"
  Prix externe: "65€"  
  Places: "12 plongeurs"
  Statut: "8 inscrits, 4 places libres"

Formation 1:
  Nom: "Stage Niveau 2"
  Dates: "Du 20 au 22 janvier 2025"
  Lieu: "Piscine + Mer"
  Instructeur: "Jean Cousteau"
  Prix: "280€"
  Prérequis: "Niveau 1 + 25 plongées"
```

### Articles/Actualités

```markdown
1. "Nouvelle épave découverte au Cap d'Antibes"
2. "Résultats du concours photo sous-marine 2024"  
3. "Planning des formations printemps 2025"
4. "Nouveau partenariat avec le centre de Cavalaire"
```

### Galeries Photos

```
Album 1: "Sortie Port-Cros - 15 janvier"
- 45 photos sous-marines
- Code d'accès: PORTCROS2025
- Participants: 12 membres

Album 2: "Formation Niveau 2 - Groupe A"  
- 23 photos pédagogiques
- Code d'accès: FORMATION-N2
- Accès: formateur + élèves uniquement
```

## 🎨 Points Forts à Mettre en Avant

### 1. Flexibilité du Système EAV
```
"Regardez comme il est facile d'ajouter des attributs spécifiques :
- Niveau de plongée (select)
- Certificat médical (fichier + date)  
- Nombre de plongées (number)
- Spécialités (text libre)

Pas besoin de modifier la base de données !"
```

### 2. Performance Ultra-Rapide
```
"L'interface d'impression photos charge en 3ms au lieu de 2 minutes !
- Cache intelligent des produits Prodigi
- Zéro appel API pendant la navigation
- Expérience utilisateur fluide"
```

### 3. Gestion des Rôles Métier
```
"Le système de rôles suit la hiérarchie plongée :
- Membres → Plongeurs → Pilotes → Directeurs → Admins
- Chaque rôle a ses permissions spécifiques
- Redirections automatiques selon le profil"
```

### 4. Multilingue Natif  
```
"Interface FR/EN prête à l'emploi :
- Traductions complètes
- Commandes de gestion des langues
- Extensible à d'autres langues"
```

## 💡 Questions/Objections Fréquentes

### "Et si j'ai besoin d'autres fonctionnalités ?"
**Réponse :** *"Le système de modules permet d'activer/désactiver les fonctionnalités. Le système EAV permet d'ajouter n'importe quel attribut sans développement."*

### "C'est compliqué à maintenir ?"
**Réponse :** *"Interface WordPress-simple mais avec la robustesse Symfony. Mises à jour via Composer, monitoring intégré, logs détaillés."*

### "Ça coûte cher en hébergement ?"
**Réponse :** *"Optimisé pour les petits hébergements : cache intelligent, requêtes optimisées, assets compressés. Compatible hébergement mutualisé."*

### "Et la sécurité ?"
**Réponse :** *"Basé sur Symfony, framework enterprise. CSRF, authentification robuste, validation des données, protection XSS/injection SQL."*

## 🎬 Script de Démonstration (15 min)

### Minutes 1-2 : Contexte
*"Imaginez un club de plongée de 150 membres avec des besoins spécifiques..."*

### Minutes 3-7 : Site Public
*"Voici l'expérience d'un membre qui veut s'inscrire à une sortie..."*

### Minutes 8-12 : Interface Admin
*"Et maintenant, côté gestionnaire du club, voyez comme c'est simple..."*

### Minutes 13-15 : Questions/Conclusion
*"Le système s'adapte à votre métier plutôt que l'inverse. Questions ?"*

## 📱 Données de Contact Démo

```
Club de Plongée Les Vénètes
📍 Port de Vannes, Morbihan  
📞 02 97 XX XX XX
✉️ contact@plongee-venetes.fr
🌐 www.plongee-venetes.fr

Président: Jean Cousteau
Directeur de Plongée: Marine Leclerc
Responsable Formation: Pierre Martin
```

## 🛠️ Checklist Avant Démo

- [ ] Base de données avec données réalistes
- [ ] Photos de qualité dans les galeries  
- [ ] Articles d'actualités récents
- [ ] Planning d'événements cohérent
- [ ] Utilisateurs avec rôles différents
- [ ] Interface responsive testée
- [ ] Cache Prodigi initialisé
- [ ] Traductions vérifiées
- [ ] Performance optimisée
- [ ] Mode démonstration activé

## 🎯 Objectifs de Conversion

À la fin de la démo, le prospect doit comprendre que ZeTilt CMS :

1. **S'adapte à son métier** (pas l'inverse)
2. **Fait gagner du temps** (admin intuitive)  
3. **Évolue avec ses besoins** (modules + EAV)
4. **Reste simple** (pas de sur-complexité technique)
5. **Offre un ROI rapide** (productivité immédiate)

---

**💼 Proposition de valeur finale :**
*"ZeTilt CMS vous donne un site professionnel qui grandit avec votre club, sans les contraintes techniques."*

Bonne démo ! 🤿🌊