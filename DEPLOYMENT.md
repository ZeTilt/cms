# Guide de déploiement en production

## Prérequis

- PHP 8.1 ou supérieur
- MySQL 5.7 ou supérieur
- Composer installé
- Accès SSH au serveur de production

## Première installation

### 1. Cloner le repository

```bash
git clone https://github.com/ZeTilt/cms.git
cd cms
```

### 2. Configuration de l'environnement

Copier et adapter le fichier `.env` :

```bash
cp .env .env.local
# Éditer .env.local avec les bonnes valeurs de production
```

Variables importantes à configurer :
- `APP_ENV=prod`
- `DATABASE_URL` avec les informations de votre base de données MySQL
- `APP_SECRET` avec une valeur aléatoire sécurisée

### 3. Déploiement initial avec données

Pour un déploiement complet avec toutes les données (pages, articles, événements, utilisateurs) :

```bash
make deploy-with-data
```

Cette commande va :
1. Installer les dépendances
2. Créer/mettre à jour le schéma de base de données
3. Charger toutes les fixtures (données initiales)
4. Activer le module blog

### 4. Créer un utilisateur administrateur supplémentaire

Si vous souhaitez créer votre propre compte admin :

```bash
php bin/console app:create-admin --env=prod
```

## Mises à jour

Pour les mises à jour ultérieures (sans recharger les données) :

```bash
make deploy
```

## Commandes utiles

### Vérifier le statut du projet
```bash
make status
```

### Vider le cache de production
```bash
make cache-clear-prod
```

### Créer une sauvegarde de la base
```bash
make backup
```

### Voir tous les utilisateurs existants

Les utilisateurs créés par défaut sont :
- `fabrice@dhuicque.fr` (SUPER_ADMIN)
- `laetitia.chapel@plongee-venetes.fr` (ADMIN)
- `berengere.desplenaire@plongee-venetes.fr` (USER)

Mot de passe par défaut : `password123`

**⚠️ IMPORTANT : Changez ces mots de passe immédiatement après le déploiement !**

## Structure des données

Les fixtures créent automatiquement :

### Pages
- Qui sommes-nous
- Où nous trouver
- Tarifs 2025
- Nos partenaires
- Formation Niveau 1, 2, 3
- Guide de palanquée
- Autres formations
- Les sorties
- Plongeurs extérieurs
- Apnée
- La piscine
- Station de gonflage

### Événements
- Plus de 50 événements répartis sur l'année
- 10 types d'événements différents (formations, sorties, baptêmes, etc.)

### Articles de blog
- 9 articles d'actualité du club

## URLs importantes

- Accueil : `/`
- Blog : `/blog`
- Calendrier : `/calendrier`
- Administration : `/admin`
- Connexion : `/login`

## Résolution de problèmes

### Le blog ne fonctionne pas

Si le blog affiche une erreur "Module not found", exécutez :

```bash
php bin/console doctrine:query:sql "INSERT INTO modules (name, display_name, description, active, config, created_at, updated_at) VALUES ('blog', 'Blog & Articles', 'Gestion du contenu blog et articles', 1, '{}', NOW(), NOW())" --env=prod
```

### Erreur de permissions

```bash
make permissions
```

### Base de données à réinitialiser

⚠️ **ATTENTION : Cette commande supprime toutes les données !**

```bash
make db-reset
make deploy-with-data
```

## Support

Pour toute question ou problème, consultez la documentation ou ouvrez une issue sur GitHub.