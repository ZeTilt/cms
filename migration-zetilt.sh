#!/bin/bash

# Migration ZeTilt CMS - Script de création de projet fonctionnel
# Usage: ./migration-zetilt.sh [nom-projet] [nom-db] [admin-email] [admin-password]

echo "🚀 Migration ZeTilt CMS - Création projet fonctionnel"
echo "=================================================="

# Variables avec valeurs par défaut
PROJECT_NAME=${1:-"mon-nouveau-site"}
DB_NAME=${2:-"zetilt_${PROJECT_NAME}"}
ADMIN_EMAIL=${3:-"admin@${PROJECT_NAME}.local"}
ADMIN_PASSWORD=${4:-"Admin123!"}

echo "📁 Projet: $PROJECT_NAME"
echo "🗄️  Base de données: $DB_NAME"  
echo "👤 Admin: $ADMIN_EMAIL"
echo ""

# Vérification des prérequis
echo "🔍 Vérification des prérequis..."
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé"
    exit 1
fi

if ! command -v composer &> /dev/null; then
    echo "❌ Composer n'est pas installé"
    exit 1
fi

if ! command -v git &> /dev/null; then
    echo "❌ Git n'est pas installé"
    exit 1
fi

echo "✅ Prérequis OK"
echo ""

# 1. Cloner le repository
echo "1️⃣ Clonage du repository..."
if [ -d "$PROJECT_NAME" ]; then
    echo "❌ Le dossier $PROJECT_NAME existe déjà"
    read -p "Supprimer et continuer ? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -rf "$PROJECT_NAME"
    else
        exit 1
    fi
fi

git clone https://github.com/ZeTilt/cms.git "$PROJECT_NAME"
cd "$PROJECT_NAME"

# 2. Réinitialiser Git
echo "2️⃣ Réinitialisation Git..."
rm -rf .git
git init
git add .
git commit -m "Initial commit - ZeTilt CMS project: $PROJECT_NAME"

# 3. Configuration environnement
echo "3️⃣ Configuration environnement..."
if [ ! -f .env ]; then
    echo "❌ Fichier .env non trouvé"
    exit 1
fi

cp .env .env.local

# Remplacer les variables dans .env.local
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed -i '' "s/zetiltcms/$DB_NAME/g" .env.local
    sed -i '' "s/APP_NAME=\"ZeTilt CMS\"/APP_NAME=\"$PROJECT_NAME\"/" .env.local
else
    # Linux
    sed -i "s/zetiltcms/$DB_NAME/g" .env.local
    sed -i "s/APP_NAME=\"ZeTilt CMS\"/APP_NAME=\"$PROJECT_NAME\"/" .env.local
fi

echo "✅ Configuration modifiée dans .env.local"

# 4. Installation dépendances
echo "4️⃣ Installation des dépendances..."
composer install --optimize-autoloader --no-dev

# Assets si npm disponible
if command -v npm &> /dev/null; then
    echo "📦 Installation assets..."
    npm install
    npm run build
else
    echo "⚠️  npm non trouvé, assets ignorés"
fi

# 5. Base de données
echo "5️⃣ Configuration base de données..."

# Tester la connexion MySQL
if command -v mysql &> /dev/null; then
    echo "🗄️  Test connexion MySQL..."
    if mysql -u root -e "SELECT 1;" &> /dev/null; then
        echo "✅ Connexion MySQL OK"
    else
        echo "⚠️  Connexion MySQL impossible avec root sans mot de passe"
        echo "   Assurez-vous que MySQL est démarré et configuré"
    fi
fi

# Créer la base
php bin/console doctrine:database:create --if-not-exists
if [ $? -eq 0 ]; then
    echo "✅ Base de données créée/trouvée"
else
    echo "❌ Erreur création base de données"
    echo "   Vérifiez votre configuration MySQL dans .env.local"
    exit 1
fi

# Migrations
php bin/console doctrine:migrations:migrate --no-interaction
if [ $? -eq 0 ]; then
    echo "✅ Migrations appliquées"
else
    echo "❌ Erreur lors des migrations"
    exit 1
fi

# 6. Initialisation complète
echo "6️⃣ Initialisation du CMS..."

# Utiliser la commande d'init complète si elle existe
if php bin/console list | grep -q "app:init-project"; then
    echo "🚀 Utilisation de la commande d'initialisation complète..."
    php bin/console app:init-project
else
    echo "📋 Initialisation manuelle..."
    
    # Liste des commandes à exécuter
    commands=(
        "app:init-cms"
        "app:init-user-types"  
        "doctrine:fixtures:load --no-interaction"
        "cache:clear"
    )
    
    for cmd in "${commands[@]}"; do
        echo "▶️  $cmd"
        if php bin/console $cmd; then
            echo "   ✅ OK"
        else
            echo "   ⚠️  Échoué ou ignoré: $cmd"
        fi
    done
    
    # Activation des modules (peut échouer si commande n'existe pas)
    modules=("UserPlus" "Events" "Gallery" "Articles" "Business")
    for module in "${modules[@]}"; do
        echo "▶️  Activation module $module"
        if php bin/console app:module:activate "$module" 2>/dev/null; then
            echo "   ✅ Module $module activé"
        else
            echo "   ⚠️  Module $module non activé (commande non trouvée)"
        fi
    done
fi

# 7. Tests finaux
echo "7️⃣ Tests finaux..."

# Test des routes
if php bin/console debug:router 2>/dev/null | grep -q admin; then
    echo "✅ Routes admin trouvées"
else
    echo "⚠️  Routes admin non trouvées"
fi

# Test de la base
if php bin/console doctrine:query:sql "SELECT COUNT(*) FROM user" 2>/dev/null | grep -q "1"; then
    echo "✅ Utilisateur admin créé"
else
    echo "⚠️  Aucun utilisateur trouvé"
fi

# Test des modules
module_count=$(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM modules" 2>/dev/null | grep -oE '[0-9]+' | tail -1)
if [ "$module_count" -gt 0 ] 2>/dev/null; then
    echo "✅ Modules trouvés ($module_count)"
else
    echo "⚠️  Aucun module trouvé"
fi

# 8. Instructions finales
echo ""
echo "🎉 Migration terminée !"
echo "======================="
echo "📂 Projet créé dans: $(pwd)"
echo ""
echo "🚀 Pour démarrer:"
echo "   symfony server:start"
echo "   # ou:"
echo "   php -S localhost:8000 -t public/"
echo ""
echo "🔐 Accès admin:"
echo "   URL: http://localhost:8000/admin"  
echo "   Email: admin@zetilt.com"
echo "   Password: Admin123!"
echo ""
echo "📊 Vérification:"
echo "   php bin/console app:module:list"
echo "   php bin/console list app:"
echo ""

# Vérifier si le serveur Symfony est disponible
if command -v symfony &> /dev/null; then
    echo "💡 Symfony CLI détecté. Démarrage automatique..."
    echo "   Accédez à: https://localhost:8000/admin"
    symfony server:start --daemon
    echo "   (serveur démarré en arrière-plan)"
else
    echo "💡 Pour installer Symfony CLI:"
    echo "   curl -sS https://get.symfony.com/cli/installer | bash"
fi

echo ""
echo "🎯 Projet $PROJECT_NAME prêt à l'emploi !"