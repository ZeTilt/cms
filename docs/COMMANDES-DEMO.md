# 🤿 Commandes pour Créer la Démo Plongée

Guide étape par étape pour préparer une démo complète d'un site de club de plongée.

## 🚀 Setup Initial (5 minutes)

```bash
# 1. Cloner et préparer le nouveau projet
git clone https://github.com/ZeTilt/cms.git demo-club-plongee
cd demo-club-plongee

# 2. Configuration environnement
cp .env .env.local

# Modifier .env.local :
# DATABASE_URL="mysql://root:@127.0.0.1:3306/demo_club_plongee?serverVersion=8.0&charset=utf8mb4"
# APP_NAME="Club de Plongée Les Vénètes"

# 3. Installation
composer install --no-dev --optimize-autoloader
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

## 📊 Création des Données de Démo

### 1. Initialisation CMS

```bash
# Initialiser le CMS de base
php bin/console app:init-cms

# Créer l'utilisateur admin principal
php bin/console app:create-admin-user
# Email: admin@plongee-venetes.fr
# Mot de passe: PlongeeDemo2025!

# Initialiser les types d'utilisateurs plongée
php bin/console app:init-user-types

# Activer tous les modules nécessaires
php bin/console app:module:activate UserPlus
php bin/console app:module:activate Events
php bin/console app:module:activate Gallery
php bin/console app:module:activate Articles
php bin/console app:module:activate Business
```

### 2. Données Utilisateurs Plongée

```bash
# Script personnalisé pour créer des plongeurs types
php bin/console app:create-diving-demo-users
```

**Si la commande n'existe pas, créer les utilisateurs manuellement via admin ou SQL :**

```sql
-- Insérer dans la base via phpMyAdmin ou console MySQL
INSERT INTO user (email, roles, password, first_name, last_name, is_verified, created_at) VALUES
('directeur@plongee-venetes.fr', '["ROLE_DIRECTEUR_PLONGEE"]', '$2y$13$hashed_password', 'Jean', 'Cousteau', 1, NOW()),
('pilote@plongee-venetes.fr', '["ROLE_PILOTE"]', '$2y$13$hashed_password', 'Marine', 'Leclerc', 1, NOW()),
('plongeur@plongee-venetes.fr', '["ROLE_PLONGEUR"]', '$2y$13$hashed_password', 'Pierre', 'Martin', 1, NOW());

-- Ajouter les attributs plongée via EAV
INSERT INTO entity_attributes (entity_type, entity_id, attribute_name, attribute_value, attribute_type) VALUES
('User', 1, 'niveau_plongee', 'MF2', 'select'),
('User', 1, 'nb_plongees', '1250', 'number'),
('User', 1, 'profondeur_max', '60', 'number'),
('User', 1, 'specialites', 'Nitrox, Épave, Profonde, Trimix', 'text'),
('User', 2, 'niveau_plongee', 'Niveau 4', 'select'),
('User', 2, 'nb_plongees', '387', 'number'),
('User', 2, 'specialites', 'Biologie, Photo sous-marine', 'text'),
('User', 3, 'niveau_plongee', 'Niveau 1', 'select'),
('User', 3, 'nb_plongees', '12', 'number'),
('User', 3, 'certificat_medical_expire', '2025-03-15', 'date');
```

### 3. Événements et Sorties de Plongée

```bash
# Créer des événements de démonstration
php bin/console app:create-diving-events-demo
```

**Événements manuels à créer via admin :**

```
Sortie 1: "Plongée Épave du Sirius"
- Date: 15/01/2025 09:00
- Lieu: Port-Cros, Var
- Attributs EAV:
  * site_plongee: "Épave du Sirius - Port-Cros"
  * profondeur_planifiee: 35
  * niveau_requis: "Niveau 2"
  * prix_membre: 45
  * prix_externe: 65
  * places_disponibles: 12

Formation 1: "Stage Niveau 2 - Session Hiver"
- Dates: 20-22/01/2025
- Lieu: Piscine Vannes + Mer
- Attributs EAV:
  * niveau_requis: "Niveau 1 + 25 plongées"
  * prix_membre: 280
  * instructeur: "Jean Cousteau"
  * places_disponibles: 8
```

### 4. Articles et Actualités

```bash
# Créer des articles de blog plongée
php bin/console app:create-diving-articles-demo
```

**Articles suggérés :**

```markdown
Article 1: "Nouvelle épave découverte au Cap d'Antibes"
Contenu: "Une épave du 18e siècle vient d'être découverte par 40m de fond..."

Article 2: "Résultats du concours photo sous-marine 2024"
Contenu: "Félicitations à tous les participants de notre concours annuel..."

Article 3: "Planning des formations printemps 2025"
Contenu: "Nous ouvrons les inscriptions pour les formations de printemps..."
```

### 5. Galeries Photos de Plongée

```bash
# Télécharger des photos de démonstration
mkdir -p public/uploads/demo-photos
cd public/uploads/demo-photos

# Télécharger des photos libres de droits (ex: Unsplash)
curl -o diving1.jpg "https://images.unsplash.com/photo-1582967788606-a171c1080cb0"
curl -o diving2.jpg "https://images.unsplash.com/photo-1559827260-dc66d52bef19"
curl -o diving3.jpg "https://images.unsplash.com/photo-1559827260-dc66d52bef19"

# Retourner au dossier projet
cd ../../..

# Créer les galeries via admin ou commande
php bin/console app:create-diving-galleries-demo
```

**Galeries à créer :**
```
Galerie 1: "Sortie Port-Cros - Janvier 2025"
- Code d'accès: PORTCROS2025
- 12 photos de plongée
- Participants: Tous les membres de la sortie

Galerie 2: "Formation Niveau 2 - Groupe A"
- Code d'accès: FORMATION-N2-A
- 8 photos pédagogiques
- Accès: Formateur + élèves uniquement
```

## 🎨 Configuration Thème Plongée

### 1. CSS Personnalisé

```css
/* Ajouter dans assets/css/app.css */
:root {
  --primary-color: #006699;     /* Bleu océan profond */
  --secondary-color: #00a86b;   /* Vert aquatique */
  --accent-color: #ff6b35;      /* Orange corail */
  --text-color: #2c3e50;
  --bg-color: #f8fafc;
  --card-shadow: 0 4px 6px rgba(0, 102, 153, 0.1);
}

.diving-hero {
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: white;
  padding: 4rem 0;
}

.diving-card {
  border-left: 4px solid var(--accent-color);
  box-shadow: var(--card-shadow);
}
```

### 2. Images de Démo

```bash
# Créer le dossier images demo
mkdir -p public/images/demo

# Télécharger des images thématiques (remplacer par vraies URLs)
curl -o public/images/demo/hero-diving.jpg "URL_PHOTO_HERO"
curl -o public/images/demo/club-team.jpg "URL_PHOTO_EQUIPE"
curl -o public/images/demo/training.jpg "URL_PHOTO_FORMATION"
curl -o public/images/demo/logo-club.png "URL_LOGO_CLUB"
```

## 🚀 Performance et Cache

```bash
# Initialiser le cache Prodigi pour impression photos
php bin/console prodigi:refresh-products --force-all

# Optimiser les performances
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Assets pour démo
npm run build
```

## 🔧 Configuration Finale

### 1. Paramètres Admin

Via l'interface admin (`/admin`), configurer :

- **Nom du site** : "Club de Plongée Les Vénètes"
- **Description** : "Club de plongée sous-marine à Vannes, Morbihan"
- **Email contact** : contact@plongee-venetes.fr
- **Téléphone** : 02 97 XX XX XX
- **Adresse** : Port de Vannes, 56000 Vannes

### 2. Modules EAV

Configurer les attributs dynamiques :

**Pour les Users (Plongeurs) :**
```
- niveau_plongee (select): N1,N2,N3,N4,MF1,MF2
- certificat_medical (file): Upload certificat
- date_certificat (date): Date d'expiration
- nb_plongees (number): Nombre total de plongées
- profondeur_max (number): Profondeur max atteinte
- specialites (text): Spécialités acquises
```

**Pour les Events (Sorties) :**
```
- site_plongee (text): Nom du site
- profondeur_planifiee (number): Profondeur en mètres
- niveau_requis (select): Niveau minimum
- prix_membre (number): Prix pour adhérents
- prix_externe (number): Prix pour externes
- places_disponibles (number): Nombre de places
- instructeur (text): Nom de l'encadrant
```

## ✅ Checklist de Validation Démo

```bash
# Vérifier que tout fonctionne
php bin/console app:test-demo-readiness

# Tests manuels à effectuer :
# □ Page d'accueil s'affiche correctement
# □ Navigation dans les menus
# □ Inscription utilisateur fonctionne  
# □ Login admin fonctionne
# □ Création d'événement dans l'admin
# □ Upload d'images dans galerie
# □ Système de rôles fonctionne
# □ Interface responsive sur mobile
# □ Performance page Prix Tirages < 100ms
```

## 🎬 Lancement de la Démo

```bash
# Démarrer le serveur de développement
php -S localhost:8000 -t public/

# Ou avec Symfony CLI
symfony server:start

# La démo est accessible sur :
# Site public: http://localhost:8000
# Admin: http://localhost:8000/admin
```

## 📊 Données de Test

**Comptes utilisateurs pour la démo :**
```
Admin: admin@plongee-venetes.fr / PlongeeDemo2025!
Directeur: directeur@plongee-venetes.fr / Plongee123!
Pilote: pilote@plongee-venetes.fr / Marine123!
Plongeur: plongeur@plongee-venetes.fr / Pierre123!
```

**URLs importantes :**
```
- Accueil: http://localhost:8000
- Admin: http://localhost:8000/admin  
- Événements: http://localhost:8000/evenements
- Galeries: http://localhost:8000/galeries
- Articles: http://localhost:8000/actualites
- Contact: http://localhost:8000/contact
```

## 🎯 Points Clés de Démonstration

1. **Flexibilité EAV** : Montrer l'ajout d'attributs sans code
2. **Performance** : Page Prix Tirages en 3ms vs 2 minutes
3. **Rôles métier** : Hiérarchie plongée native
4. **Galeries privées** : Codes d'accès par sortie
5. **Impression photos** : Intégration Prodigi fluide
6. **Responsive design** : Mobile-first
7. **Multilingue** : FR/EN prêt à l'emploi

Votre démo club de plongée est maintenant prête ! 🤿🌊