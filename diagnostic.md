# Diagnostic - Club Subaquatique des Vénètes

## Problème actuel
❌ **Erreur 500** lors de l'inscription/connexion
❌ **Connexion MySQL refusée** - identifiants incorrects ou base inexistante

## Fonctionnalités développées ✅
- ✅ Système de conditions d'inscription aux événements
- ✅ Entity Event avec champs de conditions (niveau, âge, certificat médical)  
- ✅ Entity EventParticipation pour gérer les inscriptions
- ✅ Interface admin complète pour définir les conditions
- ✅ Affichage public des conditions sur les événements
- ✅ Vérification automatique d'éligibilité des utilisateurs
- ✅ Configuration email SMTP (no-reply@venetes.dhuicque.fr)
- ✅ Contrôleur d'inscription avec validation complète

## Configuration actuelle
📧 **Email SMTP**: `smtp://no-reply@venetes.dhuicque.fr:465` (SSL)
🗄️ **Base de données**: `mysql://empo8897_venetes_preprod:***@localhost:3306/empo8897_venetes_preprod`

## Actions requises côté serveur

### 1. Vérifier la base MySQL
```bash
# Test de connexion
mysql -u empo8897_venetes_preprod -p'Vén3t3sPréPr0d'

# Si connexion OK, créer la base :
CREATE DATABASE IF NOT EXISTS empo8897_venetes_preprod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE empo8897_venetes_preprod;
```

### 2. Appliquer les migrations Doctrine
```bash
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
```

### 3. Initialiser les données
```bash
# Commandes disponibles (à vérifier) :
php bin/console app:create-admin-user --env=prod
php bin/console app:init-site-config --env=prod  
php bin/console app:create-plongee-events --env=prod
```

### 4. Tester l'application
- Inscription utilisateur → vérification email
- Connexion admin → gestion événements
- Définir conditions → test inscription événement

## Migrations à appliquer
Les nouvelles migrations incluent :
- `Version20250915090117.php` - Email verification
- `Version20250915160418.php` - Event conditions  
- `Version20250915161006.php` - Event participation

## Nouvelles tables créées
- `diving_levels` - Niveaux de plongée
- `event_participation` - Inscriptions aux événements
- Colonnes ajoutées à `events` pour les conditions
- Colonnes ajoutées à `users` pour la vérification email

## Tests disponibles
- `test-email-prod.php` - Test configuration SMTP
- `test-app.php` - Test général application
- `diagnostic.md` - Ce fichier de diagnostic