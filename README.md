# ZeTilt CMS

Un système de gestion de contenu moderne basé sur Symfony, conçu pour les développeurs et les clients.

## 🚀 Fonctionnalités

### Modules Disponibles
- **Pages** : Gestion de pages avec templates Twig personnalisés
- **Blog** : Articles avec éditeur WYSIWYG, catégories et tags
- **Galeries** : Upload d'images avec gestion des vignettes
- **Système de modules** : Activation/désactivation dynamique

### Fonctionnalités Techniques
- **Sécurité** : Sanitisation HTML, validation robuste, protection CSRF
- **Performance** : Cache intelligent, pagination optimisée
- **Développeur-friendly** : Templates Twig séparés, architecture modulaire
- **SEO** : Meta tags, slugs optimisés, sitemap automatique

## 📋 Prérequis

- PHP 8.1+
- Symfony 7.3+
- SQLite/MySQL/PostgreSQL
- Composer
- Node.js (pour Tailwind CSS)

## 🛠 Installation

### 1. Clone et installation
```bash
git clone <repository-url>
cd symfony-project
composer install
```

### 2. Configuration
```bash
# Copier le fichier d'environnement
cp .env .env.local

# Configurer la base de données dans .env.local
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

### 3. Base de données
```bash
# Créer la base de données
php bin/console doctrine:database:create

# Appliquer les migrations
php bin/console doctrine:migrations:migrate

# Créer un utilisateur admin
php bin/console app:create-user admin@example.com password "Admin User" --admin --super-admin
```

### 4. Initialiser les modules
```bash
# Initialiser le module Blog
php bin/console app:init-blog-module
```

### 5. Démarrer le serveur
```bash
symfony serve --no-tls
```

## 🏗 Architecture

### Structure des Dossiers
```
src/
├── Controller/          # Contrôleurs (Admin, Public, API)
├── Entity/             # Entités Doctrine (Page, Article, User, etc.)
├── Repository/         # Repositories Doctrine
├── Service/           # Services métier
├── EventListener/     # Listeners d'événements
├── Command/          # Commandes console
└── Twig/            # Extensions Twig

templates/
├── admin/           # Templates administration
├── blog/           # Templates blog public
├── pages/          # Templates pages personnalisés
└── components/     # Composants réutilisables

public/
├── uploads/        # Fichiers uploadés
└── assets/        # Assets statiques
```

### Modules Système

#### Module Pages
- **Entité** : `App\Entity\Page`
- **Contrôleur Admin** : `App\Controller\PagesController`
- **Contrôleur Public** : `App\Controller\PublicPagesController`
- **Service** : `App\Service\PageTemplateService`

**Workflow Pages** :
1. Création page dans l'admin → Template Twig auto-généré
2. Développeur édite `templates/pages/[slug].html.twig`
3. Rendu public utilise le template personnalisé

#### Module Blog
- **Entité** : `App\Entity\Article`
- **Contrôleur Admin** : `App\Controller\ArticleController`
- **Contrôleur Public** : `App\Controller\BlogController`
- **Services** : `ContentSanitizer`, `ArticleValidator`, `CacheService`

**Fonctionnalités Blog** :
- Éditeur WYSIWYG (Quill.js)
- Catégories et tags
- Pagination
- Cache intelligent
- Sanitisation de contenu

#### Module Galeries
- **Entité** : `App\Entity\Gallery`, `App\Entity\Image`
- **Contrôleur** : `App\Controller\GalleryController`
- **Service** : `App\Service\ImageUploadService`

## 🔐 Sécurité

### Niveaux d'Accès
- **ROLE_USER** : Accès de base
- **ROLE_ADMIN** : Gestion du contenu
- **ROLE_SUPER_ADMIN** : Gestion des modules

### Mesures de Sécurité
- **Sanitisation HTML** : HTMLPurifier pour le contenu
- **Validation** : Services de validation robustes
- **CSRF** : Protection sur tous les formulaires
- **Upload** : Validation stricte des fichiers

## ⚡ Performance

### Cache
- **Articles** : Cache par page et limite
- **Catégories/Tags** : Cache longue durée
- **Invalidation** : Automatique via listeners

### Optimisations
- **Pagination** : Queries optimisées
- **Images** : Vignettes automatiques
- **Templates** : Compilation Twig

### Monitoring
- **MonitoringService** : Collecte automatique de métriques
- **Types de métriques** : Performance, cache, sécurité, contenu
- **Nettoyage** : Commande `app:monitoring:cleanup` pour éviter la surcharge BDD
- **Rétention** : 72h recommandé pour le debug, plus pour l'analyse

## 🧪 Tests

### Lancer les Tests
```bash
# Tests unitaires
php bin/phpunit tests/Unit/

# Tests fonctionnels
php bin/phpunit tests/Functional/

# Tous les tests
php bin/phpunit
```

### Coverage
```bash
php bin/phpunit --coverage-html coverage/
```

## 🔧 Développement

### Commandes Utiles
```bash
# Créer un utilisateur
php bin/console app:create-user email password "Full Name"

# Initialiser les modules
php bin/console app:init-blog-module

# Nettoyer les métriques de monitoring (recommandé quotidiennement)
php bin/console app:monitoring:cleanup --older-than-hours=72

# Vider le cache
php bin/console cache:clear

# Mise à jour de la BDD
php bin/console doctrine:migrations:migrate
```

### Workflows Développeur

#### Créer une Nouvelle Page
1. Aller dans Admin → Pages → Nouvelle page
2. Remplir titre, slug, méta-données
3. Sauvegarder → Template auto-créé
4. Éditer `templates/pages/[slug].html.twig`

#### Créer un Nouvel Article
1. Activer module Blog si nécessaire
2. Admin → Articles → Nouvel article
3. Utiliser l'éditeur WYSIWYG
4. Ajouter catégories/tags
5. Publier

#### Ajouter un Nouveau Module
1. Créer entité dans `src/Entity/`
2. Créer contrôleur dans `src/Controller/`
3. Créer templates dans `templates/`
4. Enregistrer le module : `ModuleManager::registerModule()`
5. Ajouter liens conditionnels dans la navigation

## 📊 Base de Données

### Schéma Principal
```sql
-- Pages
CREATE TABLE pages (
    id INTEGER PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    template_path VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL,
    -- ... autres champs
);

-- Articles
CREATE TABLE articles (
    id INTEGER PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    status VARCHAR(20) NOT NULL,
    category VARCHAR(100),
    tags JSON,
    -- ... autres champs
);

-- Modules
CREATE TABLE modules (
    id INTEGER PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    active BOOLEAN DEFAULT FALSE,
    -- ... autres champs
);
```

## 🚀 Déploiement

### Production
```bash
# Optimiser l'autoloader
composer install --no-dev --optimize-autoloader

# Vider et réchauffer le cache
php bin/console cache:clear --env=prod

# Optimiser les assets
php bin/console assets:install --env=prod

# Configurer le nettoyage automatique des métriques (cron)
# Ajouter à crontab : 0 2 * * * /path/to/php /path/to/bin/console app:monitoring:cleanup --older-than-hours=72
```

### Variables d'Environnement
```env
APP_ENV=prod
APP_SECRET=your-secret-key
DATABASE_URL=mysql://user:pass@localhost:3306/zetilt_cms
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit les changements (`git commit -am 'Add nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

## 📝 Changelog

### v1.0.0 (2025-01-23)
- ✅ Système de pages avec templates Twig
- ✅ Module blog complet avec WYSIWYG
- ✅ Système de modules dynamiques
- ✅ Galeries avec upload d'images
- ✅ Cache et optimisations
- ✅ Sécurité renforcée

## 📄 License

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🆘 Support

- **Documentation** : Ce README et commentaires dans le code
- **Issues** : Utiliser le système d'issues GitHub
- **Email** : contact@zetilt.com

---

**ZeTilt CMS** - Un CMS moderne pour développeurs exigeants 🚀