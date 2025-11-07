# Checklist de déploiement en production

## Avant le déploiement (sur votre machine locale)

### 1. Préparer les clés VAPID
```bash
# Générer les clés (une seule fois)
./generate-vapid-keys.sh
# OU
npx web-push generate-vapid-keys --json
```

**⚠️ Important** : Notez ces clés dans un endroit sûr (gestionnaire de mots de passe)

### 2. Vérifier les fichiers PWA
```bash
make deploy-check
```

Vérifiez que vous avez :
- [ ] `public/sw.js`
- [ ] `public/manifest.json`
- [ ] `public/js/push-notifications.js`
- [ ] `public/pwa-icons/` (tous les icônes)

### 3. Tester localement
```bash
# Démarrer le serveur
make start

# Dans un autre terminal, tester
make test-notifications
```

### 4. Commit et push
```bash
git add .
git commit -m "feat: ajout système notifications PWA"
git push origin main
```

## Sur le serveur de production

### 1. Configurer l'environnement

```bash
# Créer le fichier de configuration
cp .env.prod.local.example .env.prod.local
nano .env.prod.local
```

Remplir :
- [ ] `DATABASE_URL` (connexion base de données)
- [ ] `APP_SECRET` (clé secrète unique)
- [ ] `VAPID_PUBLIC_KEY` (clé générée précédemment)
- [ ] `VAPID_PRIVATE_KEY` (clé générée précédemment)
- [ ] `VAPID_SUBJECT` (votre email)

### 2. Déployer

```bash
# Méthode 1 : Via le script PHP
php deploy.php

# Méthode 2 : Via Make
make deploy
```

### 3. Vérifier les migrations

```bash
php bin/console doctrine:migrations:status --env=prod
```

Vérifier que ces migrations sont exécutées :
- [ ] Version20251107131853 (table push_subscriptions)
- [ ] Version20251107143408 (table notification_history)
- [ ] Version20251107144317 (champ notify_event_reminder)
- [ ] Version20251107150231 (champ notify_new_event)

### 4. Vérifier les tables

```bash
php bin/console doctrine:query:sql "SHOW TABLES" --env=prod | grep -E "(push|notification)"
```

Vous devez voir :
- [ ] `push_subscriptions`
- [ ] `notification_history`

### 5. Tester le service worker

1. Ouvrir le site en production
2. Ouvrir DevTools (F12)
3. Onglet "Application" > "Service Workers"
4. Vérifier qu'il est actif

### 6. Tester les notifications

1. Se connecter sur `/profile`
2. Cliquer sur "Activer" dans la section notifications
3. Accepter la permission
4. Vérifier "✅ Notifications activées"

### 7. (Optionnel) Configurer le cron pour les rappels

```bash
# Éditer la crontab
crontab -e

# Ajouter cette ligne (rappels à 10h chaque jour)
0 10 * * * cd /chemin/vers/projet && php bin/console app:send-event-reminders --env=prod >> var/log/cron.log 2>&1
```

## Tests post-déploiement

### Test 1 : Activation des notifications
- [ ] Aller sur `/profile`
- [ ] Activer les notifications
- [ ] Vérifier la présence dans la table push_subscriptions

```bash
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM push_subscriptions" --env=prod
```

### Test 2 : Création d'événement
- [ ] Créer un nouvel événement avec un niveau minimum
- [ ] Vérifier que les utilisateurs éligibles reçoivent une notification
- [ ] Vérifier l'entrée dans notification_history

```bash
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM notification_history WHERE type='new_event'" --env=prod
```

### Test 3 : Inscription à un événement
- [ ] S'inscrire à un événement (en tant que participant)
- [ ] Vérifier que le DP reçoit une notification
- [ ] Vérifier dans le dashboard analytics : `/dp/notifications/analytics`

### Test 4 : Rappels
```bash
# Test en dry-run
php bin/console app:send-event-reminders --dry-run --env=prod

# Test réel (si événements dans 24h)
php bin/console app:send-event-reminders --env=prod
```

## En cas de problème

### Les notifications ne fonctionnent pas

```bash
# 1. Vérifier les clés VAPID
grep VAPID .env.prod.local

# 2. Vérifier les logs
tail -f var/log/prod.log

# 3. Tester la connexion DB
php bin/console doctrine:query:sql "SELECT 1" --env=prod

# 4. Vérifier les permissions
ls -la var/cache var/log

# 5. Re-déployer si nécessaire
php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

### Service Worker ne s'installe pas

1. Vérifier que le site est en **HTTPS** (obligatoire pour PWA)
2. Vérifier que `sw.js` est accessible : `https://votre-site.com/sw.js`
3. Vider le cache du navigateur (Ctrl+Shift+Del)
4. Désinstaller l'ancien SW dans DevTools > Application > Service Workers

### Erreur "Invalid VAPID key"

Les clés doivent être générées avec `web-push generate-vapid-keys`. Ne pas utiliser de clés aléatoires.

Régénérer :
```bash
./generate-vapid-keys.sh
# Puis mettre à jour .env.prod.local
# Puis redémarrer le serveur/PHP-FPM
```

## Rollback en cas de problème critique

```bash
# 1. Rollback Git
git revert HEAD
git push origin main

# 2. Rollback migrations (avec précaution)
php bin/console doctrine:migrations:migrate prev --env=prod

# 3. Nettoyer le cache
php bin/console cache:clear --env=prod
```

## Support

Documentation complète : voir `DEPLOY_PWA.md`

Logs à surveiller :
- `var/log/prod.log` (logs applicatifs)
- `var/log/cron.log` (si cron configuré)
