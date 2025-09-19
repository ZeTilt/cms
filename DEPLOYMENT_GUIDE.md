# 🚀 Guide de Déploiement Sécurisé - Nouvelles Fonctionnalités Plongée

## ⚠️ IMPORTANT: Déploiement SANS PERTE DE DONNÉES

Ce déploiement ajoute de nouvelles fonctionnalités sans supprimer de données existantes.

## 📋 Checklist Pré-Déploiement

### 1. Backup de la Base de Données (OBLIGATOIRE)
```bash
# Sur le serveur de production
mysqldump -u [user] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Vérifier les Migrations
Les migrations ajoutées sont **non-destructives** :
- `Version20250919060820.php` : Ajoute des colonnes (pas de suppression)
- `Version20250919064527.php` : Ajoute relation User-DivingLevel (pas de suppression)

## 📦 Étapes de Déploiement

### Étape 1: Pull du Code
```bash
git pull origin main
```

### Étape 2: Installation des Dépendances
```bash
composer install --no-dev --optimize-autoloader
```

### Étape 3: Vérifier l'État des Migrations
```bash
# Voir les migrations en attente (SANS les exécuter)
php bin/console doctrine:migrations:status
```

### Étape 4: Exécuter les Migrations (SAFE)
```bash
# Exécuter les migrations une par une pour plus de contrôle
php bin/console doctrine:migrations:migrate --no-interaction
```

### Étape 5: Ajouter les Niveaux de Plongée
```bash
# Exécuter uniquement si la table diving_levels est vide
php bin/console dbal:run-sql "
INSERT INTO diving_levels (name, code, description, sort_order, is_active, created_at)
VALUES
('Débutant', 'BEGINNER', 'Niveau débutant en plongée', 1, 1, NOW()),
('Niveau 1', 'N1', 'Plongeur Niveau 1 FFESSM', 2, 1, NOW()),
('Niveau 2', 'N2', 'Plongeur Niveau 2 FFESSM', 3, 1, NOW()),
('Niveau 3', 'N3', 'Plongeur Niveau 3 FFESSM', 4, 1, NOW()),
('Niveau 4', 'N4', 'Plongeur Niveau 4 FFESSM', 5, 1, NOW()),
('Instructeur', 'INSTRUCTOR', 'Instructeur de plongée', 10, 1, NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name)
"
```

### Étape 6: Clear Cache
```bash
php bin/console cache:clear
php bin/console cache:warmup
```

### Étape 7: Vérifier les Permissions Fichiers
```bash
# Ajuster selon votre configuration serveur
chown -R www-data:www-data var/
chmod -R 775 var/cache var/log
```

## 🔍 Vérifications Post-Déploiement

### 1. Test des Nouvelles Routes
- `/dp/events` - Interface DP (nécessite ROLE_DP)
- `/admin/events/new` - Vérifier les nouveaux champs

### 2. Attribuer le Rôle DP
```sql
-- Pour donner le rôle DP à un utilisateur existant
UPDATE users
SET roles = JSON_ARRAY_APPEND(roles, '$', 'ROLE_DP')
WHERE email = 'dp@club.com';
```

### 3. Tests Fonctionnels
- [ ] Création d'événement avec heures de RDV
- [ ] Création d'événement avec niveau minimum
- [ ] Inscription avec choix de point de RDV
- [ ] Système de liste d'attente
- [ ] Récurrence d'événements

## 🔄 Rollback si Nécessaire

### Annuler les Migrations (en cas d'urgence)
```bash
# Revenir à la version précédente
php bin/console doctrine:migrations:migrate 'DoctrineMigrations\Version20250917120011' --no-interaction

# Restaurer le backup
mysql -u [user] -p [database_name] < backup_[timestamp].sql
```

### Revenir au Code Précédent
```bash
git reset --hard HEAD~1
composer install --no-dev --optimize-autoloader
php bin/console cache:clear
```

## ✅ Changements de la Base de Données

### Tables Modifiées (SANS PERTE)
- `event` : Ajout de colonnes (non-destructif)
  - `min_diving_level_id` (nullable)
  - `club_meeting_time` (nullable)
  - `site_meeting_time` (nullable)

- `event_participation` : Ajout de colonnes (non-destructif)
  - `meeting_point` (nullable)
  - `is_waiting_list` (default false)

- `users` : Ajout de colonne (non-destructif)
  - `highest_diving_level_id` (nullable)

### Nouvelles Tables (création uniquement)
- `diving_levels` (si n'existe pas déjà)

## 📝 Notes Importantes

1. **Aucune donnée existante n'est supprimée**
2. **Toutes les nouvelles colonnes sont nullable ou ont des valeurs par défaut**
3. **Les migrations sont réversibles**
4. **Le système reste compatible avec les données existantes**

## 🆘 En Cas de Problème

1. Vérifier les logs : `tail -f var/log/prod.log`
2. Vérifier l'état de la base : `php bin/console doctrine:schema:validate`
3. Si erreur migration : Restaurer le backup et analyser l'erreur

## 📞 Support

En cas de problème lors du déploiement, les migrations sont conçues pour être sûres et réversibles.
Toutes les modifications sont additives (ajout de colonnes/tables) sans suppression de données.