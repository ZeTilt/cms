# Guide de déploiement - Notifications PWA

Ce document décrit les étapes spécifiques pour déployer les fonctionnalités de notifications push.

## Prérequis

### 1. Générer les clés VAPID (une seule fois)

Les clés VAPID sont nécessaires pour l'authentification des notifications push. Elles doivent être générées **avant** le premier déploiement.

```bash
# Sur votre machine locale (nécessite Node.js)
npx web-push generate-vapid-keys --json
```

Exemple de sortie :
```json
{
  "publicKey": "BFZwKctIv3TiawBfJTBJmbYkhl_g9SEpmYqnT-TTgu_HYmXIeGxe_H33acq09PhRGLUp8E1hN6MVg8LjeJoFUVw",
  "privateKey": "1Oqt9SoMRvR1Lqzk3FNYy4HOrUGmpegv5Gk4hy4ZvuA"
}
```

### 2. Configurer .env.prod.local

Ajoutez ces lignes dans votre fichier `.env.prod.local` sur le serveur :

```bash
###> Web Push Notifications ###
VAPID_PUBLIC_KEY=votre_clé_publique_ici
VAPID_PRIVATE_KEY=votre_clé_privée_ici
VAPID_SUBJECT=mailto:contact@plongee-venetes.fr
###< Web Push Notifications ###
```

⚠️ **Important** : Ces clés sont sensibles. Ne les commitez JAMAIS dans Git !

## Déploiement

### 1. Déploiement standard

```bash
# Sur le serveur
php deploy.php
```

Le script vérifiera automatiquement :
- ✅ Présence des clés VAPID
- ✅ Fichiers PWA (manifest.json, sw.js, push-notifications.js)
- ✅ Migrations des tables (push_subscriptions, notification_history)

### 2. Vérifications post-déploiement

#### A. Vérifier les tables

```bash
php bin/console doctrine:query:sql "SHOW TABLES LIKE '%push%'" --env=prod
php bin/console doctrine:query:sql "SHOW TABLES LIKE '%notification%'" --env=prod
```

Vous devriez voir :
- `push_subscriptions`
- `notification_history`

#### B. Vérifier le Service Worker

1. Ouvrez votre site en production
2. Ouvrez les DevTools (F12)
3. Onglet "Application" > "Service Workers"
4. Vous devriez voir un service worker actif pour votre domaine

#### C. Tester les notifications

1. Connectez-vous sur `/profile`
2. Section "🔔 Notifications push"
3. Cliquez sur "Activer"
4. Acceptez la permission dans le navigateur
5. Vous devriez voir "✅ Notifications activées"

## Migrations incluses

Les migrations suivantes ont été créées pour les notifications PWA :

1. **Version20251107131853** : Table `push_subscriptions`
   - Stocke les abonnements aux notifications
   - Champs : endpoint, keys, preferences

2. **Version20251107143408** : Table `notification_history`
   - Historique de toutes les notifications envoyées
   - Tracking : envoi, livraison, ouverture, clic

3. **Version20251107144317** : Champ `notify_event_reminder`
   - Ajout préférence pour rappels 24h avant événement

4. **Version20251107150231** : Champ `notify_new_event`
   - Ajout préférence pour nouvelles plongées créées

## Fonctionnalités déployées

### Types de notifications

1. **Inscriptions** (pour DP)
   - Notifie le DP quand un participant s'inscrit
   - Inclut info liste d'attente si applicable

2. **Désinscriptions** (pour DP)
   - Notifie le DP quand un participant se désinscrit
   - Affiche le nombre de places restantes

3. **Promotion liste d'attente**
   - Notifie l'utilisateur promu
   - Notifie aussi le DP

4. **Rappels 24h avant**
   - Envoyé via commande cron : `app:send-event-reminders`
   - À tous les participants confirmés

5. **Nouvelles plongées** (NOUVEAU)
   - Notifie les utilisateurs éligibles selon leur niveau
   - Vérifie automatiquement minDivingLevel vs highestDivingLevel

### Préférences utilisateur

Chaque utilisateur peut activer/désactiver dans son profil :
- ✅ Nouvelles inscriptions (DP uniquement)
- ✅ Désinscriptions (DP uniquement)
- ✅ Place libérée (liste d'attente)
- ✅ Nouvelles plongées (selon niveau)
- ✅ Notifications DP (toutes notifications DP)

## Configuration Cron (optionnel)

Pour les rappels automatiques 24h avant les événements :

```bash
# Ajouter dans crontab
0 10 * * * cd /chemin/vers/projet && php bin/console app:send-event-reminders --env=prod
```

Cela enverra les rappels tous les jours à 10h.

## Analytics

Un dashboard est disponible pour les DP à l'adresse :
```
/dp/notifications/analytics
```

Statistiques disponibles :
- Nombre total de notifications envoyées
- Taux de livraison
- Taux d'ouverture
- Taux de clic
- Breakdown par type de notification

## Troubleshooting

### Les notifications ne fonctionnent pas

1. **Vérifier les clés VAPID**
   ```bash
   grep VAPID .env.prod.local
   ```

2. **Vérifier les tables**
   ```bash
   php bin/console doctrine:query:sql "SELECT COUNT(*) FROM push_subscriptions" --env=prod
   ```

3. **Vérifier les logs**
   ```bash
   tail -f var/log/prod.log | grep -i "push\|notification"
   ```

### Service Worker ne s'installe pas

1. Vérifier que le site est en HTTPS (requis pour PWA)
2. Vérifier que `sw.js` est accessible : https://votre-site.com/sw.js
3. Vider le cache du navigateur et recharger

### Erreur "Invalid VAPID key"

- Les clés VAPID doivent être générées avec `web-push generate-vapid-keys`
- Ne pas utiliser de clés aléatoires, elles doivent suivre le format ECDH P-256

## Support

Pour toute question sur le déploiement :
1. Vérifier les logs : `var/log/prod.log`
2. Lancer les diagnostics : `php bin/console doctrine:migrations:status --env=prod`
3. Tester manuellement : `php bin/console app:send-event-reminders --dry-run --env=prod`

## Checklist finale

Avant de déclarer le déploiement réussi :

- [ ] Clés VAPID configurées dans .env.prod.local
- [ ] Tables push_subscriptions et notification_history créées
- [ ] Service Worker accessible et actif
- [ ] Test d'activation des notifications sur /profile OK
- [ ] Test de création d'événement → notification reçue
- [ ] Dashboard analytics accessible (/dp/notifications/analytics)
- [ ] Cron configuré pour les rappels (optionnel)
