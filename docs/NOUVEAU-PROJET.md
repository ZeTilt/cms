# 🚀 Créer un Nouveau Projet basé sur ZeTilt CMS

Ce guide vous explique comment créer un nouveau projet en utilisant ZeTilt CMS comme base, spécialement adapté pour un **site de club de plongée**.

## 📋 Prérequis

- **PHP 8.2+**
- **Composer**
- **Node.js & npm** (pour les assets)
- **MySQL/MariaDB**
- **Git**

## 🏗️ Étapes de Création du Nouveau Projet

### 1. Cloner le Repository Base

```bash
# Cloner le repository ZeTilt CMS
git clone https://github.com/ZeTilt/cms.git mon-site-plongee
cd mon-site-plongee

# Supprimer l'historique Git existant et créer un nouveau repo
rm -rf .git
git init
git add .
git commit -m "Initial commit - ZeTilt CMS for diving club"

# Connecter à votre nouveau repository (optionnel)
git remote add origin https://github.com/votre-username/mon-site-plongee.git
git push -u origin main
```

### 2. Configuration de l'Environnement

```bash
# Copier le fichier d'environnement
cp .env .env.local

# Modifier .env.local avec vos paramètres
```

**Exemple de configuration `.env.local` pour club de plongée :**

```bash
# Base de données
DATABASE_URL="mysql://utilisateur:motdepasse@127.0.0.1:3306/mon_club_plongee?serverVersion=8.0&charset=utf8mb4"

# Configuration du site
APP_NAME="Club de Plongée Les Vénètes"
APP_DESCRIPTION="Club de plongée sous-marine à Vannes"
APP_VERSION="1.0.0"

# Email
MAILER_DSN=smtp://localhost:1025

# API Prodigi (pour impression photos)
PRODIGI_API_KEY="votre-cle-api-prodigi"
PRODIGI_ENVIRONMENT="sandbox" # ou "live" pour production

# Paramètres spécifiques plongée
DIVING_SEASON_START="04-01" # 1er avril
DIVING_SEASON_END="10-31"   # 31 octobre
MAX_DIVE_DEPTH="40"         # Profondeur max en mètres
CERTIFICATION_VALIDITY_YEARS="3"
```

### 3. Installation et Configuration

```bash
# Installer les dépendances PHP
composer install

# Installer les dépendances JavaScript
npm install

# Créer la base de données
php bin/console doctrine:database:create

# Appliquer les migrations
php bin/console doctrine:migrations:migrate

# Charger les données de base pour club de plongée
php bin/console doctrine:fixtures:load
```

### 4. Configuration Spécifique Club de Plongée

#### A. Initialiser le CMS pour la plongée

```bash
# Initialiser les modules de base
php bin/console app:init-cms
php bin/console app:init-user-types

# Activer les modules spécifiques plongée
php bin/console app:module:activate UserPlus
php bin/console app:module:activate Events  
php bin/console app:module:activate Gallery
php bin/console app:module:activate Articles
php bin/console app:module:activate Business
```

#### B. Configurer les rôles de plongée

Le système inclut déjà la hiérarchie des rôles de plongée :
- `ROLE_USER` → Membre du club
- `ROLE_PLONGEUR` → Plongeur certifié  
- `ROLE_PILOTE` → Pilote de palanquée
- `ROLE_DIRECTEUR_PLONGEE` → Directeur de plongée
- `ROLE_ADMIN` → Administrateur du club
- `ROLE_SUPER_ADMIN` → Super administrateur

### 5. Personnalisation pour Club de Plongée

#### A. Créer les Types d'Événements de Plongée

```bash
# Créer des types d'événements personnalisés
php bin/console app:create-event-types
```

#### B. Configurer les Attributs Dynamiques (EAV)

Ajoutez ces attributs pour les plongeurs dans l'admin :

**Pour les Utilisateurs (Plongeurs) :**
- `niveau_plongee` (select) : Niveau 1, Niveau 2, Niveau 3, Niveau 4, MF1, MF2
- `certificat_medical` (file) : Certificat médical
- `date_certificat` (date) : Date du certificat
- `nb_plongees` (number) : Nombre de plongées  
- `profondeur_max` (number) : Profondeur maximale
- `specialites` (text) : Spécialités (Nitrox, Épave, etc.)

**Pour les Événements (Sorties Plongée) :**
- `site_plongee` (text) : Site de plongée
- `profondeur_planifiee` (number) : Profondeur prévue
- `niveau_requis` (select) : Niveau minimum requis
- `prix_membre` (number) : Prix pour membres
- `prix_externe` (number) : Prix pour externes
- `places_disponibles` (number) : Nombre de places

### 6. Contenu de Démo Club de Plongée

#### A. Créer du Contenu de Base

```bash
# Créer des pages de démonstration
php bin/console app:create-demo-pages

# Créer des galeries photos de plongée  
php bin/console app:create-demo-galleries

# Créer des articles de blog sur la plongée
php bin/console app:create-demo-articles
```

#### B. Structure de Pages Suggérée

**Pages principales à créer :**
- **Accueil** : Présentation du club
- **Le Club** : Histoire, équipe, valeurs
- **Formations** : Niveaux, stages, prix
- **Sorties** : Planning, sites de plongée
- **Galerie** : Photos/vidéos des sorties
- **Actualités** : Blog du club
- **Contact** : Coordonnées, formulaire

### 7. Configuration Avancée

#### A. Système d'Impression Photos (Prodigi)

```bash
# Configurer le cache des produits Prodigi
php bin/console prodigi:refresh-products --force-all

# Programmer le refresh automatique (crontab)
# Ajouter dans crontab -e :
# 0 6 * * * cd /path/to/project && php bin/console prodigi:refresh-products >/dev/null 2>&1
```

#### B. Configuration Email

Configurez votre serveur email pour :
- Confirmations d'inscription aux événements
- Notifications aux membres
- Rappels certificats médicaux
- Newsletter du club

### 8. Thème et Design

#### A. Personnaliser les Couleurs

Modifiez dans `assets/css/app.css` :

```css
:root {
  --primary-color: #0066cc;     /* Bleu océan */
  --secondary-color: #00a86b;   /* Vert aquatique */  
  --accent-color: #ffb347;      /* Orange corail */
  --text-color: #333;
  --bg-color: #f8f9fa;
}
```

#### B. Logo et Images

Remplacez dans `public/images/` :
- `logo.png` : Logo du club
- `hero-diving.jpg` : Image d'accueil
- `about-club.jpg` : Photo de l'équipe
- `training-photo.jpg` : Photo formation

### 9. Sécurité et Production

#### A. Configuration Production

```bash
# Générer les secrets de production
php bin/console secrets:generate-keys --env=prod

# Construire les assets pour production
npm run build

# Vider le cache
php bin/console cache:clear --env=prod
```

#### B. Configuration Serveur Web

**Apache (.htaccess déjà configuré)**

**Nginx :**
```nginx
server {
    listen 80;
    server_name mon-club-plongee.fr;
    root /var/www/mon-site-plongee/public;
    
    location / {
        try_files $uri /index.php$is_args$args;
    }
    
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }
}
```

## 📁 Structure du Projet Club de Plongée

```
mon-site-plongee/
├── config/                 # Configuration Symfony
├── migrations/             # Migrations base de données
├── public/                 # Point d'entrée web
│   ├── uploads/           # Photos, certificats, etc.
│   └── images/            # Assets statiques
├── src/
│   ├── Controller/        # Contrôleurs
│   │   ├── Admin/        # Interface d'administration
│   │   └── Public/       # Site public
│   ├── Entity/           # Entités (User, Event, Gallery, etc.)
│   ├── Service/          # Services métier
│   │   ├── DivingService.php      # Logique plongée
│   │   ├── ProdigiApiService.php  # Photos
│   │   └── ModuleManager.php      # Gestion modules
│   └── Repository/       # Requêtes base de données
├── templates/
│   ├── admin/            # Templates admin
│   ├── public/           # Templates site public
│   └── components/       # Composants réutilisables
└── translations/         # Traductions FR/EN
```

## 🎯 Fonctionnalités Spécifiques Club de Plongée

### ✅ Modules Activés par Défaut

1. **UserPlus** : Gestion membres avec attributs dynamiques
2. **Events** : Planning sorties et formations  
3. **Gallery** : Albums photos avec codes d'accès
4. **Articles** : Blog et actualités du club
5. **Business** : Formulaires contact et témoignages

### 🚀 Fonctionnalités Avancées

- **Système de réservation** pour les sorties
- **Gestion des niveaux** et certifications
- **Planning des formations** avec instructeurs  
- **Galeries photos privées** par sortie
- **Impression de photos** via Prodigi API
- **Notifications automatiques** (certificats, événements)
- **Interface multilingue** (FR/EN)

## 🛠️ Commandes Utiles

```bash
# Gestion des modules
php bin/console app:module:list
php bin/console app:module:activate Events

# Gestion des utilisateurs
php bin/console app:create-admin-user
php bin/console app:init-user-types

# Maintenance
php bin/console app:monitoring:cleanup
php bin/console cache:clear

# Tests
php bin/phpunit tests/WorkingFeaturesTest.php
```

## 📞 Support et Documentation

- **Documentation technique** : `/docs/`
- **Tests fonctionnels** : `/tests/WorkingFeaturesTest.php`  
- **Fichier de configuration** : `CLAUDE.md`

---

## 🎉 Félicitations !

Votre site de club de plongée est maintenant prêt ! 

**Prochaines étapes :**
1. Personnaliser le contenu dans l'admin
2. Configurer les événements/sorties
3. Ajouter vos photos dans les galeries
4. Inviter les premiers membres
5. Tester les fonctionnalités de réservation

**Interface d'administration :** `https://votre-site.fr/admin`

Bon développement ! 🤿🌊