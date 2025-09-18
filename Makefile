# Makefile pour le site de plongée
# Variables
PHP = php
COMPOSER = composer
NODE = node
NPM = npm

# Couleurs pour les messages
GREEN = \033[0;32m
YELLOW = \033[0;33m
RED = \033[0;31m
NC = \033[0m # No Color

.PHONY: help install install-dev start stop test lint fix migrate cache-clear assets deploy status

help: ## Affiche cette aide
	@echo "$(GREEN)Makefile pour le site de plongée$(NC)"
	@echo ""
	@echo "$(YELLOW)Commandes disponibles:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'

install: ## Installation complète (production)
	@echo "$(GREEN)🚀 Installation en production...$(NC)"
	$(COMPOSER) install --no-dev --optimize-autoloader
	$(PHP) bin/console cache:clear --env=prod
	$(PHP) bin/console doctrine:migrations:migrate --no-interaction --env=prod
	@echo "$(GREEN)✅ Installation terminée$(NC)"

install-dev: ## Installation complète (développement)
	@echo "$(GREEN)🔧 Installation en développement...$(NC)"
	$(COMPOSER) install
	$(PHP) bin/console cache:clear
	$(PHP) bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(GREEN)✅ Installation de développement terminée$(NC)"

start: ## Démarre le serveur de développement
	@echo "$(GREEN)🚀 Démarrage du serveur...$(NC)"
	$(PHP) -S localhost:8000 -t public

stop: ## Arrête le serveur (Ctrl+C)
	@echo "$(YELLOW)⚠️  Utilisez Ctrl+C pour arrêter le serveur$(NC)"

test: ## Lance les tests
	@echo "$(GREEN)🧪 Lancement des tests...$(NC)"
	$(PHP) bin/phpunit

lint: ## Vérifie le code (PHP CS Fixer)
	@echo "$(GREEN)🔍 Vérification du code...$(NC)"
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

fix: ## Corrige automatiquement le code
	@echo "$(GREEN)🔧 Correction automatique du code...$(NC)"
	$(PHP) vendor/bin/php-cs-fixer fix

migrate: ## Lance les migrations de base de données
	@echo "$(GREEN)🗄️  Lancement des migrations...$(NC)"
	$(PHP) bin/console doctrine:migrations:migrate --no-interaction

migrate-prod: ## Lance les migrations en production
	@echo "$(GREEN)🗄️  Lancement des migrations (production)...$(NC)"
	$(PHP) bin/console doctrine:migrations:migrate --no-interaction --env=prod

rollback: ## Rollback à la migration précédente
	@echo "$(YELLOW)⚠️  Rollback à la migration précédente...$(NC)"
	$(PHP) bin/console doctrine:migrations:migrate prev --no-interaction

cache-clear: ## Vide le cache
	@echo "$(GREEN)🗑️  Vidage du cache...$(NC)"
	$(PHP) bin/console cache:clear

cache-clear-prod: ## Vide le cache de production
	@echo "$(GREEN)🗑️  Vidage du cache de production...$(NC)"
	$(PHP) bin/console cache:clear --env=prod

assets: ## Compile les assets
	@echo "$(GREEN)📦 Compilation des assets...$(NC)"
	$(NPM) run build

watch: ## Surveille les changements d'assets
	@echo "$(GREEN)👀 Surveillance des assets...$(NC)"
	$(NPM) run watch

# Commandes de base de données
db-create: ## Crée la base de données
	@echo "$(GREEN)🗄️  Création de la base de données...$(NC)"
	$(PHP) bin/console doctrine:database:create --if-not-exists

db-drop: ## Supprime la base de données
	@echo "$(RED)⚠️  Suppression de la base de données...$(NC)"
	$(PHP) bin/console doctrine:database:drop --force --if-exists

db-truncate: ## Vide toutes les tables sans les supprimer
	@echo "$(YELLOW)🗑️  Vidage de toutes les tables...$(NC)"
	$(PHP) bin/console doctrine:schema:drop --full-database --force
	$(PHP) bin/console doctrine:migrations:migrate --no-interaction

db-reset: db-drop db-create migrate ## Recrée complètement la base
	@echo "$(GREEN)🔄 Base de données recréée$(NC)"

# Commandes utilisateur
user-create: ## Crée un utilisateur admin
	@echo "$(GREEN)👤 Création d'un utilisateur admin...$(NC)"
	$(PHP) bin/console app:create-admin

user-create-prod: ## Crée un utilisateur admin (production)
	@echo "$(GREEN)👤 Création d'un utilisateur admin (production)...$(NC)"
	$(PHP) bin/console app:create-admin --env=prod

# Commandes de déploiement
deploy-check: ## Vérifie avant déploiement
	@echo "$(GREEN)🔍 Vérifications avant déploiement...$(NC)"
	$(COMPOSER) validate --no-check-publish --no-check-all
	$(PHP) bin/console lint:container
	$(PHP) bin/console doctrine:mapping:info

deploy: deploy-check ## Déploie en production
	@echo "$(GREEN)🚀 Déploiement en production...$(NC)"
	git pull origin main
	$(COMPOSER) install --no-dev --optimize-autoloader
	$(PHP) bin/console cache:clear --env=prod
	$(PHP) bin/console doctrine:migrations:migrate --no-interaction --env=prod
	@echo "$(GREEN)✅ Déploiement terminé$(NC)"

deploy-with-data: deploy ## Déploie en production avec les données initiales
	@echo "$(GREEN)📊 Chargement des données initiales...$(NC)"
	$(PHP) bin/console doctrine:fixtures:load --no-interaction --env=prod
	$(PHP) bin/console doctrine:query:sql "INSERT INTO modules (name, display_name, description, active, config, created_at, updated_at) VALUES ('blog', 'Blog & Articles', 'Gestion du contenu blog et articles', 1, '{}', NOW(), NOW())" --env=prod 2>/dev/null || true
	@echo "$(GREEN)✅ Données chargées$(NC)"

status: ## Affiche le statut du projet
	@echo "$(GREEN)📊 Statut du projet$(NC)"
	@echo "$(YELLOW)Git:$(NC)"
	@git status --short
	@echo ""
	@echo "$(YELLOW)Composer:$(NC)"
	@$(COMPOSER) outdated --direct --no-dev 2>/dev/null || echo "Tous les packages sont à jour"
	@echo ""
	@echo "$(YELLOW)Base de données:$(NC)"
	@$(PHP) bin/console doctrine:migrations:status --show-versions

# Commandes de maintenance  
logs: ## Affiche les logs
	@echo "$(GREEN)📋 Affichage des logs...$(NC)"
	tail -f var/log/*.log

clear-logs: ## Vide les logs
	@echo "$(GREEN)🗑️  Vidage des logs...$(NC)"
	rm -f var/log/*.log

permissions: ## Corrige les permissions
	@echo "$(GREEN)🔐 Correction des permissions...$(NC)"
	chmod -R 755 .
	chmod -R 777 var/cache var/log public/uploads

# Commandes de développement
dev-reset: ## Reset complet pour développement
	@echo "$(GREEN)🔄 Reset complet...$(NC)"
	$(MAKE) db-reset
	$(MAKE) cache-clear
	$(MAKE) user-create
	@echo "$(GREEN)✅ Reset terminé$(NC)"

quality: ## Lance tous les contrôles qualité
	@echo "$(GREEN)✨ Contrôles qualité...$(NC)"
	$(MAKE) lint
	$(MAKE) test
	$(MAKE) deploy-check

# Commandes spécifiques au projet
setup-plongee: ## Configuration spécifique plongée
	@echo "$(GREEN)🤿 Configuration du site de plongée...$(NC)"
	$(PHP) bin/console app:init-site-config
	$(PHP) bin/console app:create-plongee-pages
	$(PHP) bin/console app:create-plongee-events

# Backup et dump
backup: ## Crée une sauvegarde de la base
	@echo "$(GREEN)💾 Création d'une sauvegarde...$(NC)"
	@mkdir -p backups
	$(PHP) bin/console app:backup-database backups/backup_$(shell date +%Y%m%d_%H%M%S).sql

dump-local: ## Dump de la base locale MySQL
	@echo "$(GREEN)📦 Dump de la base locale...$(NC)"
	@mkdir -p dumps
	@mysqldump -u empo8897_venetes_preprod -p'Vén3t3sPréPr0d' --single-transaction --no-tablespaces empo8897_venetes_preprod > dumps/local_$(shell date +%Y%m%d_%H%M%S).sql 2>/dev/null || true
	@echo "$(GREEN)✅ Dump créé dans dumps/$(NC)"

dump-data-only: ## Dump des données uniquement (sans structure)
	@echo "$(GREEN)📦 Dump des données seulement...$(NC)"
	@mkdir -p dumps
	@mysqldump -u empo8897_venetes_preprod -p'Vén3t3sPréPr0d' --no-create-info --single-transaction empo8897_venetes_preprod > dumps/data_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✅ Dump des données créé dans dumps/$(NC)"

restore-local: ## Restaure un dump dans la base locale (usage: make restore-local DUMP=fichier.sql)
	@echo "$(GREEN)📥 Restauration de $(DUMP)...$(NC)"
	@mysql -u empo8897_venetes_preprod -p'Vén3t3sPréPr0d' empo8897_venetes_preprod < $(DUMP)
	@echo "$(GREEN)✅ Base restaurée$(NC)"

# Aide par défaut
default: help