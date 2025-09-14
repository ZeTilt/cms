# Déploiement sur O2switch

## 1. Préparation locale

### Configuration de production
Créez le fichier `.env.prod.local` avec vos paramètres O2switch :

```bash
###> symfony/framework-bundle ###
APP_ENV=prod
APP_SECRET=VotreSecretKey32CaracteresMinimum123
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
# Remplacez par vos paramètres O2switch
DATABASE_URL="mysql://votre_user:votre_password@localhost:3306/votre_db?serverVersion=8.0&charset=utf8mb4"
###< doctrine/doctrine-bundle ###
```

### Génération d'une clé secrète
```bash
php -r "echo bin2hex(random_bytes(32));"
```

## 2. Upload des fichiers

### Via FTP/SFTP
Uploadez tous les fichiers du projet **SAUF** :
- `var/cache/`
- `var/log/`
- `.env.local`
- `.env.dev`

### Structure sur O2switch
```
www/ (ou public_html/)
├── public/          # Point d'entrée web
├── src/
├── config/
├── templates/
├── migrations/
├── vendor/
├── .env
├── .env.prod
├── .env.prod.local  # Vos paramètres de production
├── composer.json
├── deploy.php
└── ...
```

## 3. Configuration de la base de données

### Dans le panneau O2switch
1. Créez une base de données MySQL
2. Notez les paramètres : nom, utilisateur, mot de passe
3. Mettez à jour `.env.prod.local` avec ces paramètres

## 4. Déploiement

### Connexion SSH (si disponible)
```bash
ssh votre_user@votre_domaine.com
cd www/  # ou public_html/
php deploy.php
```

### Alternative sans SSH
Si pas d'accès SSH, exécutez manuellement :

```bash
# 1. Installer les dépendances (sur votre machine avant upload)
composer install --no-dev --optimize-autoloader

# 2. Puis uploadez le dossier vendor/
```

Créez ensuite `setup.php` sur le serveur :
```php
<?php
// setup.php - À exécuter une seule fois via navigateur web
require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv('.env');

// Vider le cache
exec('php bin/console cache:clear --env=prod --no-debug 2>&1', $output);
echo "Cache: " . implode('<br>', $output) . "<br><br>";

// Migrations
exec('php bin/console doctrine:migrations:migrate --no-interaction --env=prod 2>&1', $output);
echo "Migrations: " . implode('<br>', $output) . "<br><br>";

echo "Setup terminé! Supprimez ce fichier.";
?>
```

## 5. Configuration Apache

### .htaccess racine
Le fichier `.htaccess` à la racine redirige vers `public/` :
```apache
DirectoryIndex public/index.php
RewriteEngine On
RewriteCond %{THE_REQUEST} /public/([^\s?]*) [NC]
RewriteRule ^ /%1 [NC,L,R]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*) /public/$1 [NC,L]
```

## 6. Post-installation

### Créer un utilisateur admin
```bash
php bin/console app:create-admin
# Ou via navigateur web si pas de SSH : créez admin-setup.php
```

### Configuration du site
1. Connectez-vous à `/admin`
2. Allez dans "Configuration" 
3. Remplissez les informations du club

### Créer des pages de contenu
```bash
php bin/console app:create-plongee-pages
php bin/console app:create-plongee-events
```

## 7. Vérifications

### URLs à tester
- `https://votre-domaine.com/` - Page d'accueil
- `https://votre-domaine.com/admin` - Interface admin
- `https://votre-domaine.com/calendrier` - Calendrier
- `https://votre-domaine.com/blog` - Blog

### Logs d'erreur
- Vérifiez `var/log/prod.log` pour les erreurs
- Consultez les logs Apache d'O2switch

## 8. Maintenance

### Mise à jour du contenu
- Utilisez l'interface admin pour gérer articles, événements, pages
- Upload d'images via l'éditeur Summernote

### Sauvegardes
- Base de données : via phpMyAdmin ou commandes MySQL
- Fichiers : sauvegarde du dossier `public/uploads/`

## Problèmes fréquents

### Erreur 500
- Vérifiez les permissions (755 pour dossiers, 644 pour fichiers)
- Consultez les logs dans `var/log/prod.log`
- Vérifiez la configuration base de données

### Assets non trouvés
- Vérifiez que le dossier `public/assets/` est uploadé
- Contrôlez les permissions

### Page blanche
- Mode debug : changez temporairement `APP_ENV=dev` dans `.env.prod.local`
- Regardez les logs d'erreur PHP