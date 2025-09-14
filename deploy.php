<?php
// Script de déploiement simple pour O2switch
// Usage: php deploy.php

echo "🚀 Déploiement du site plongée...\n\n";

// 1. Vérifier l'environnement
echo "📋 Vérification de l'environnement...\n";
if (!file_exists('.env.prod.local')) {
    echo "❌ Fichier .env.prod.local manquant!\n";
    echo "   Créez ce fichier avec vos paramètres de production.\n";
    exit(1);
}

// 2. Installation des dépendances
echo "📦 Installation des dépendances...\n";
exec('composer install --no-dev --optimize-autoloader 2>&1', $output, $return);
if ($return !== 0) {
    echo "❌ Erreur lors de l'installation des dépendances:\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}
echo "✅ Dépendances installées\n\n";

// 3. Vider le cache
echo "🗑️  Nettoyage du cache...\n";
exec('php bin/console cache:clear --env=prod --no-debug 2>&1', $output, $return);
if ($return !== 0) {
    echo "❌ Erreur lors du nettoyage du cache:\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}
echo "✅ Cache nettoyé\n\n";

// 4. Migrations de la base de données
echo "🗄️  Mise à jour de la base de données...\n";
exec('php bin/console doctrine:migrations:migrate --no-interaction --env=prod 2>&1', $output, $return);
if ($return !== 0) {
    echo "⚠️  Avertissement lors des migrations:\n";
    echo implode("\n", $output) . "\n";
}
echo "✅ Base de données mise à jour\n\n";

// 5. Créer les dossiers nécessaires
echo "📁 Création des dossiers...\n";
$dirs = ['var/cache', 'var/log', 'public/uploads'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "   ✅ Dossier $dir créé\n";
    }
}

// 6. Permissions
echo "🔐 Configuration des permissions...\n";
chmod('var', 0755);
chmod('var/cache', 0755);
chmod('var/log', 0755);
chmod('public/uploads', 0755);
echo "✅ Permissions configurées\n\n";

echo "🎉 Déploiement terminé avec succès!\n\n";
echo "📝 N'oubliez pas de:\n";
echo "   1. Vérifier votre fichier .env.prod.local\n";
echo "   2. Tester l'accès admin avec: php bin/console app:create-admin\n";
echo "   3. Configurer les informations du site via l'admin\n";
?>