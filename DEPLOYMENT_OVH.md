# Guide de Déploiement - OVH VPS

## Prérequis serveur

- VPS OVH (Ubuntu 22.04 LTS recommandé)
- Accès SSH root
- Nom de domaine pointant vers le VPS

## 1. Configuration initiale du serveur

```bash
# Connexion SSH
ssh root@IP_SERVEUR

# Mise à jour système
apt update && apt upgrade -y

# Installation des paquets essentiels
apt install -y git curl unzip nginx mysql-server php8.3-fpm \
  php8.3-mysql php8.3-xml php8.3-mbstring php8.3-zip php8.3-curl \
  php8.3-intl php8.3-gd php8.3-imagick certbot python3-certbot-nginx

# Si PHP 8.3 non disponible, ajouter le PPA :
add-apt-repository ppa:ondrej/php
apt update
apt install php8.3-fpm php8.3-mysql php8.3-xml php8.3-mbstring php8.3-zip php8.3-curl php8.3-intl php8.3-gd php8.3-imagick
```

## 2. Configuration MySQL

```bash
# Sécurisation MySQL
mysql_secure_installation

# Création de la base et de l'utilisateur
mysql -u root -p
```

```sql
CREATE DATABASE venetes_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'venetes_user'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_SECURISE';
GRANT ALL PRIVILEGES ON venetes_prod.* TO 'venetes_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Installation Composer

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

## 4. Clonage du projet

```bash
# Créer l'utilisateur web (si pas déjà fait)
useradd -m -s /bin/bash webuser
usermod -aG www-data webuser

# Cloner le repo
cd /var/www
git clone https://github.com/ZeTilt/cms.git venetes
cd venetes
chown -R webuser:www-data /var/www/venetes
```

## 5. Configuration de l'application

```bash
# Copier l'environnement de production
cp .env .env.local

# Éditer .env.local avec les vraies valeurs
nano .env.local
```

**Contenu de `.env.local` :**

```env
APP_ENV=prod
APP_SECRET=GENERER_UNE_CLE_SECRETE_64_CHARS
APP_DEBUG=false

DATABASE_URL="mysql://venetes_user:MOT_DE_PASSE@localhost:3306/venetes_prod?serverVersion=8.0&charset=utf8mb4"

MAILER_DSN=smtp://user:pass@smtp.brevo.com:587

# CACI Encryption (générer avec: openssl rand -hex 32)
CACI_ENCRYPTION_KEY=GENERER_CLE_32_BYTES_HEX

# Club info
CLUB_NAME="Club Subaquatique des Vénètes"
CLUB_EMAIL="contact@venetes-plongee.fr"
```

**Générer les clés :**

```bash
# APP_SECRET
php -r "echo bin2hex(random_bytes(32));"

# CACI_ENCRYPTION_KEY
openssl rand -hex 32
```

## 6. Installation des dépendances

```bash
cd /var/www/venetes

# En tant que webuser
su - webuser
cd /var/www/venetes

# Installation prod (sans dev)
composer install --no-dev --optimize-autoloader

# Cache et assets
php bin/console cache:clear --env=prod
php bin/console assets:install --env=prod

# Migrations base de données
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

## 7. Permissions

```bash
# Retour en root
exit

# Permissions correctes
chown -R webuser:www-data /var/www/venetes
chmod -R 755 /var/www/venetes
chmod -R 775 /var/www/venetes/var
chmod -R 775 /var/www/venetes/public/uploads

# Dossier CACI (stockage chiffré)
mkdir -p /var/www/venetes/var/caci_storage
chmod 700 /var/www/venetes/var/caci_storage
chown webuser:www-data /var/www/venetes/var/caci_storage
```

## 8. Configuration Nginx

```bash
nano /etc/nginx/sites-available/venetes
```

```nginx
server {
    listen 80;
    server_name venetes-plongee.fr www.venetes-plongee.fr;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name venetes-plongee.fr www.venetes-plongee.fr;

    root /var/www/venetes/public;
    index index.php;

    # SSL sera configuré par Certbot
    # ssl_certificate /etc/letsencrypt/live/venetes-plongee.fr/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/venetes-plongee.fr/privkey.pem;

    # Logs
    access_log /var/log/nginx/venetes-access.log;
    error_log /var/log/nginx/venetes-error.log;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # Cache static assets
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|webp|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    # Sécurité
    location ~ /\. {
        deny all;
    }
    location ~ ^/(var|config|vendor)/ {
        deny all;
    }
}
```

```bash
# Activer le site
ln -s /etc/nginx/sites-available/venetes /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

## 9. SSL avec Let's Encrypt

```bash
certbot --nginx -d venetes-plongee.fr -d www.venetes-plongee.fr
```

## 10. Cron Jobs

```bash
crontab -e
```

Ajouter :

```cron
# Rappels CACI (tous les lundis à 9h)
0 9 * * 1 cd /var/www/venetes && php bin/console app:caci:reminder --env=prod >> /var/log/caci-reminder.log 2>&1

# Rétention CACI - suppression certificats expirés (tous les jours à 3h)
0 3 * * * cd /var/www/venetes && php bin/console app:caci:retention --env=prod >> /var/log/caci-retention.log 2>&1

# Nettoyage cache Symfony (tous les dimanches à 4h)
0 4 * * 0 cd /var/www/venetes && php bin/console cache:clear --env=prod >> /var/log/symfony-cache.log 2>&1
```

## 11. Déploiement des mises à jour

Créer un script de déploiement :

```bash
nano /var/www/venetes/deploy.sh
chmod +x /var/www/venetes/deploy.sh
```

```bash
#!/bin/bash
# deploy.sh - Script de déploiement

set -e

cd /var/www/venetes

echo "=== Déploiement Vénètes ==="
echo "Date: $(date)"

# 1. Pull des changements
echo "[1/5] Git pull..."
git pull origin main

# 2. Composer install (prod)
echo "[2/5] Composer install..."
composer install --no-dev --optimize-autoloader

# 3. Migrations
echo "[3/5] Migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# 4. Cache clear
echo "[4/5] Cache clear..."
php bin/console cache:clear --env=prod

# 5. Assets
echo "[5/5] Assets install..."
php bin/console assets:install --env=prod

echo "=== Déploiement terminé ==="
```

**Utilisation :**

```bash
cd /var/www/venetes && ./deploy.sh
```

## 12. Monitoring et logs

```bash
# Logs Symfony
tail -f /var/www/venetes/var/log/prod.log

# Logs Nginx
tail -f /var/log/nginx/venetes-error.log

# Logs PHP-FPM
tail -f /var/log/php8.3-fpm.log
```

## 13. Checklist post-déploiement

- [ ] Le site répond sur https://venetes-plongee.fr
- [ ] Connexion admin fonctionne
- [ ] Upload d'images fonctionne
- [ ] Envoi d'emails fonctionne (tester via contact)
- [ ] Certificats SSL valides
- [ ] Cron jobs configurés
- [ ] Backups automatiques configurés (voir ci-dessous)

## 14. Backups automatiques

```bash
# Script de backup
nano /root/backup-venetes.sh
chmod +x /root/backup-venetes.sh
```

```bash
#!/bin/bash
# backup-venetes.sh

BACKUP_DIR="/root/backups"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup BDD
mysqldump -u venetes_user -p'MOT_DE_PASSE' venetes_prod | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/venetes/public/uploads

# Backup CACI (chiffré)
tar -czf $BACKUP_DIR/caci_$DATE.tar.gz /var/www/venetes/var/caci_storage

# Garder seulement les 7 derniers backups
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "Backup terminé: $DATE"
```

```bash
# Cron backup quotidien
crontab -e
# Ajouter:
0 2 * * * /root/backup-venetes.sh >> /var/log/backup-venetes.log 2>&1
```

## Résumé des commandes principales

```bash
# Déploiement
./deploy.sh

# Voir les logs
tail -f var/log/prod.log

# Vider le cache
php bin/console cache:clear --env=prod

# Forcer les migrations
php bin/console doctrine:migrations:migrate --env=prod

# Créer un admin
php bin/console app:create-admin email@example.com MotDePasse --env=prod
```
