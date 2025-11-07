# Guide de Maintenance

[⬅️ Retour à l'index](README.md) | [⬅️ Dette Technique](11-dette-technique.md)

## 🚀 Installation et Configuration

### Prérequis

- PHP 8.2 ou supérieur
- Composer 2.x
- MySQL 8.0+ ou SQLite 3
- Symfony CLI (recommandé)

### Installation Initiale

```bash
# Clone repository
git clone <repository-url> mon-site-plongee
cd mon-site-plongee

# Installation dépendances
composer install

# Configuration environnement
cp .env .env.local
# Éditer .env.local avec vos paramètres

# Création base de données
php bin/console doctrine:database:create

# Exécution migrations
php bin/console doctrine:migrations:migrate

# Chargement fixtures (optionnel)
php bin/console doctrine:fixtures:load

# Lancement serveur dev
symfony serve
```

### Variables d'Environnement

```env
# .env.local
APP_ENV=dev
APP_SECRET=<générer-avec-symfony-console-secrets-generate>

# Database
DATABASE_URL="mysql://user:password@127.0.0.1:3306/venetes?serverVersion=8.0"

# Mailer
MAILER_DSN=smtp://user:pass@smtp.example.com:587

# Uploads
UPLOAD_MAX_SIZE=10485760  # 10MB
```

---

## 🔄 Workflows de Développement

### Workflow Git

**Branches :**
```
main         → Production
develop      → Développement
feature/*    → Nouvelles fonctionnalités
fix/*        → Corrections bugs
hotfix/*     → Corrections urgentes production
```

**Processus :**

```bash
# Nouvelle fonctionnalité
git checkout develop
git pull origin develop
git checkout -b feature/nom-feature

# Développement...
git add .
git commit -m "feat: description"

# Avant de push
composer install
vendor/bin/phpstan analyse src
vendor/bin/phpunit

# Push et PR
git push origin feature/nom-feature
# Créer PR vers develop
```

**Conventions commits :**
```
feat: nouvelle fonctionnalité
fix: correction bug
refactor: refactoring sans changement fonctionnel
docs: documentation
test: ajout/modification tests
chore: tâches maintenance
perf: amélioration performance
```

---

## 🧪 Tests

### Exécution Tests

```bash
# Tous les tests
vendor/bin/phpunit

# Tests spécifiques
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Functional

# Avec couverture
vendor/bin/phpunit --coverage-html var/coverage
```

### Création Tests

**Test unitaire service :**

```php
<?php
namespace App\Tests\Unit\Service;

use App\Service\ParticipationManager;
use PHPUnit\Framework\TestCase;

class ParticipationManagerTest extends TestCase
{
    public function testRegisterUserToEvent(): void
    {
        // Arrange
        $user = $this->createMock(User::class);
        $event = $this->createMock(Event::class);

        // Act
        $participation = $this->manager->register($user, $event);

        // Assert
        $this->assertInstanceOf(EventParticipation::class, $participation);
        $this->assertEquals('confirmed', $participation->getStatus());
    }
}
```

**Test fonctionnel contrôleur :**

```php
<?php
namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CalendarControllerTest extends WebTestCase
{
    public function testCalendarPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/calendrier');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Calendrier');
    }
}
```

---

## 🗄️ Base de Données

### Migrations

**Créer migration :**

```bash
# Auto-génération depuis entités
php bin/console make:migration

# Exécuter migrations
php bin/console doctrine:migrations:migrate

# Rollback dernière migration
php bin/console doctrine:migrations:migrate prev
```

**Migration manuelle :**

```php
<?php
// migrations/VersionXXX.php

public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE event ADD COLUMN notes TEXT');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE event DROP COLUMN notes');
}
```

### Fixtures

**Créer fixtures :**

```php
<?php
namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $event = new Event();
            $event->setTitle("Événement $i");
            $event->setStartDate(new \DateTime("+$i days"));
            $manager->persist($event);
        }

        $manager->flush();
    }
}
```

**Charger fixtures :**

```bash
php bin/console doctrine:fixtures:load
```

---

## 🐛 Debugging

### Symfony Profiler

```
http://localhost:8000/_profiler
```

**Informations disponibles :**
- Requêtes SQL
- Performance
- Logs
- Erreurs
- Variables

### Dump & Die

```php
dump($variable);
dd($variable);  // Dump and Die
```

### Logs

```bash
# Logs temps réel
tail -f var/log/dev.log

# Erreurs uniquement
tail -f var/log/dev.log | grep ERROR
```

### Debug Routes

```bash
php bin/console debug:router
php bin/console debug:router admin_event_index
```

### Debug Services

```bash
php bin/console debug:container
php bin/console debug:container ParticipationManager
```

---

## 🚀 Déploiement

### Déploiement Production

**Checklist pré-déploiement :**

```bash
# 1. Tests
vendor/bin/phpunit

# 2. Analyse statique
vendor/bin/phpstan analyse src

# 3. Vérifier sécurité
symfony check:security

# 4. Optimiser autoloader
composer dump-autoload --optimize --classmap-authoritative

# 5. Clear cache
php bin/console cache:clear --env=prod
```

**Déploiement :**

```bash
# Sur serveur production
git pull origin main
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear
php bin/console cache:warmup

# Permissions
chmod -R 777 var/
```

### Configuration Serveur Web

**Nginx :**

```nginx
server {
    server_name venetes.example.com;
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
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

**Apache (.htaccess déjà présent) :**

Vérifier que mod_rewrite est activé.

---

## 🔧 Maintenance Courante

### Tâches Quotidiennes

```bash
# Vérifier logs erreurs
tail -n 100 var/log/prod.log | grep ERROR

# Backup base de données
mysqldump -u user -p venetes > backup_$(date +%Y%m%d).sql
```

### Tâches Hebdomadaires

```bash
# Vérifier dépendances obsolètes
composer outdated

# Vérifier vulnérabilités
symfony check:security

# Nettoyer cache
php bin/console cache:clear --env=prod
```

### Tâches Mensuelles

```bash
# Mettre à jour dépendances
composer update

# Exécuter tests complets
vendor/bin/phpunit

# Analyser métriques
vendor/bin/phpmetrics --report-html=var/metrics src

# Backup complet
tar -czf backup_$(date +%Y%m%d).tar.gz \
    --exclude='var/cache' \
    --exclude='var/log' \
    --exclude='vendor' \
    .
```

---

## 📦 Gestion Modules

### Activer/Désactiver Module

```bash
# Via interface admin
# /admin/modules

# Ou directement en DB
UPDATE module SET active = 1 WHERE name = 'blog';
```

### Créer Nouveau Module

```php
// 1. Créer entrée DB
INSERT INTO module (name, display_name, description, active, config)
VALUES ('shop', 'Boutique', 'Vente matériel plongée', 1, '{}');

// 2. Créer contrôleurs dans src/Controller/Shop/

// 3. Créer templates dans templates/shop/

// 4. Conditionner navigation
{% if is_module_active('shop') %}
    <a href="{{ path('shop_index') }}">Boutique</a>
{% endif %}
```

---

## 🆘 Dépannage

### Problèmes Fréquents

**1. Erreur "Class not found"**

```bash
composer dump-autoload
php bin/console cache:clear
```

**2. Erreur migration**

```bash
# Voir état migrations
php bin/console doctrine:migrations:status

# Force version
php bin/console doctrine:migrations:version VersionXXX --add
```

**3. Permissions var/**

```bash
chmod -R 777 var/
# Ou mieux:
chown -R www-data:www-data var/
```

**4. CSRF token invalide**

```bash
php bin/console cache:clear
# Vérifier session.cookie_secure dans config
```

**5. Upload échoue**

```bash
# Vérifier php.ini
upload_max_filesize = 10M
post_max_size = 10M

# Vérifier permissions
chmod 777 public/uploads
```

---

## 📚 Ressources

### Documentation Symfony

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Twig](https://twig.symfony.com/doc/)

### Outils Utiles

- [Symfony CLI](https://symfony.com/download)
- [PHPStorm Symfony Plugin](https://plugins.jetbrains.com/plugin/7219-symfony-support)
- [Postman](https://www.postman.com/) - Test API

### Communauté

- [Symfony Slack](https://symfony.com/slack)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/symfony)

---

## ✅ Checklist Maintenance

### Avant Chaque Release

- [ ] Tests passent (vendor/bin/phpunit)
- [ ] Analyse statique OK (phpstan)
- [ ] Pas de vulnérabilités (symfony check:security)
- [ ] Documentation à jour
- [ ] Migrations testées
- [ ] Backup base de données créé
- [ ] Variables .env.prod configurées
- [ ] Cache production généré

### Après Chaque Release

- [ ] Vérifier logs erreurs
- [ ] Tester fonctionnalités critiques
- [ ] Vérifier emails envoyés
- [ ] Monitoring actif
- [ ] Documentation déploiement mise à jour

---

**Fin de la Documentation**

Pour toute question ou suggestion, contacter l'équipe de développement.

[⬅️ Retour à l'index](README.md)
