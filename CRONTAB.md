# Configuration Crontab - Club Vénètes

## Installation

Éditer le crontab de l'utilisateur web (www-data ou l'utilisateur du serveur) :

```bash
crontab -e
```

## Tâches planifiées

### 1. Rappels CACI (Certificat médical)

Envoie des emails aux plongeurs dont le CACI expire dans 30 jours et 7 jours.

```cron
# Rappels CACI - tous les jours à 8h00
0 8 * * * cd /home/empo8897/venetes.dhuicque.fr && php bin/console app:caci:send-reminders --env=prod >> var/log/cron-caci.log 2>&1
```

### 2. Rappels événements

Envoie des rappels aux participants inscrits aux événements à venir.

```cron
# Rappels événements - tous les jours à 18h00
0 18 * * * cd /home/empo8897/venetes.dhuicque.fr && php bin/console app:send-event-reminders --env=prod >> var/log/cron-events.log 2>&1
```

### 3. Nettoyage du cache (optionnel)

Vide le cache de production périodiquement pour éviter les problèmes.

```cron
# Nettoyage cache - tous les dimanches à 4h00
0 4 * * 0 cd /home/empo8897/venetes.dhuicque.fr && php bin/console cache:clear --env=prod >> var/log/cron-cache.log 2>&1
```

### 4. Optimisation des images (optionnel)

Optimise les nouvelles images uploadées (compression + WebP).

```cron
# Optimisation images - tous les jours à 3h00
0 3 * * * cd /home/empo8897/venetes.dhuicque.fr && php bin/console app:optimize-images --env=prod >> var/log/cron-images.log 2>&1
```

## Crontab complet

Copier-coller ce bloc dans `crontab -e` :

```cron
# ============================================
# CRONTAB - Club de Plongée Vénètes
# ============================================

# Variables d'environnement
SHELL=/bin/bash
PATH=/usr/local/bin:/usr/bin:/bin
MAILTO=""

# Répertoire du projet
PROJECT_DIR=/home/empo8897/venetes.dhuicque.fr

# --------------------------------------------
# TÂCHES QUOTIDIENNES
# --------------------------------------------

# 03:00 - Optimisation des images
0 3 * * * cd $PROJECT_DIR && php bin/console app:optimize-images --env=prod >> var/log/cron-images.log 2>&1

# 08:00 - Rappels CACI (certificats médicaux)
0 8 * * * cd $PROJECT_DIR && php bin/console app:caci:send-reminders --env=prod >> var/log/cron-caci.log 2>&1

# 18:00 - Rappels événements
0 18 * * * cd $PROJECT_DIR && php bin/console app:send-event-reminders --env=prod >> var/log/cron-events.log 2>&1

# --------------------------------------------
# TÂCHES HEBDOMADAIRES
# --------------------------------------------

# Dimanche 04:00 - Nettoyage cache
0 4 * * 0 cd $PROJECT_DIR && php bin/console cache:clear --env=prod >> var/log/cron-cache.log 2>&1

# Dimanche 04:30 - Rotation des logs cron (garde 4 semaines)
30 4 * * 0 cd $PROJECT_DIR && find var/log -name 'cron-*.log' -mtime +28 -delete
```

## Vérification

Pour vérifier que les crons sont bien configurés :

```bash
# Lister les crons actifs
crontab -l

# Tester une commande manuellement
cd /home/empo8897/venetes.dhuicque.fr
php bin/console app:caci:send-reminders --dry-run --env=prod
php bin/console app:send-event-reminders --dry-run --env=prod
```

## Logs

Les logs des tâches cron sont stockés dans `var/log/` :
- `cron-caci.log` - Rappels CACI
- `cron-events.log` - Rappels événements
- `cron-images.log` - Optimisation images
- `cron-cache.log` - Nettoyage cache

Pour surveiller les logs en temps réel :
```bash
tail -f var/log/cron-*.log
```

## Dépannage

Si un cron ne fonctionne pas :

1. Vérifier que PHP est accessible : `which php`
2. Vérifier les permissions du dossier var/ : `chmod -R 775 var/`
3. Tester la commande manuellement avec `--env=prod`
4. Vérifier les logs d'erreur : `cat var/log/cron-*.log`
