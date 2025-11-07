<?php
// Script de déploiement robuste pour O2switch
// Usage: php deploy.php

echo "🚀 Déploiement du site plongée...\n\n";

// 1. Vérifier l'environnement
echo "📋 Vérification de l'environnement...\n";
if (!file_exists('.env.prod.local')) {
    echo "❌ Fichier .env.prod.local manquant!\n";
    echo "   Créez ce fichier avec vos paramètres de production.\n";
    exit(1);
}

// Vérifier les clés VAPID pour les notifications push
echo "🔑 Vérification des clés VAPID...\n";
$envContent = file_get_contents('.env.prod.local');
if (strpos($envContent, 'VAPID_PUBLIC_KEY') === false ||
    strpos($envContent, 'VAPID_PRIVATE_KEY') === false) {
    echo "⚠️  Clés VAPID manquantes dans .env.prod.local!\n";
    echo "   Les notifications push ne fonctionneront pas sans ces clés.\n";
    echo "   Pour les générer, utilisez: npx web-push generate-vapid-keys --json\n";
    echo "   Ajoutez-les dans .env.prod.local:\n";
    echo "   VAPID_PUBLIC_KEY=votre_clé_publique\n";
    echo "   VAPID_PRIVATE_KEY=votre_clé_privée\n";
    echo "   VAPID_SUBJECT=mailto:contact@plongee-venetes.fr\n\n";
} else {
    echo "✅ Clés VAPID présentes\n";
}

// 2. Installation des dépendances (sans scripts auto)
echo "📦 Installation des dépendances...\n";
exec('composer install --no-dev --optimize-autoloader --no-scripts 2>&1', $output, $return);
if ($return !== 0) {
    echo "⚠️  Avertissement lors de l'installation:\n";
    echo implode("\n", $output) . "\n";
} else {
    echo "✅ Dépendances installées\n";
}

// 3. Créer les dossiers nécessaires AVANT le cache
echo "📁 Création des dossiers...\n";
$dirs = ['var/cache', 'var/log', 'var/cache/prod', 'public/uploads'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "   ✅ Dossier $dir créé\n";
        }
    }
}

// 4. Vider le cache (avec gestion d'erreur)
echo "🗑️  Nettoyage du cache...\n";
exec('php bin/console cache:clear --env=prod --no-debug 2>&1', $output, $return);
if ($return !== 0) {
    echo "⚠️  Cache: tentative de nettoyage manuel...\n";
    // Nettoyage manuel si échec
    exec('rm -rf var/cache/prod/* 2>/dev/null || true');
    exec('php bin/console cache:warmup --env=prod --no-debug 2>&1', $output2, $return2);
    if ($return2 === 0) {
        echo "✅ Cache réchauffé manuellement\n";
    } else {
        echo "⚠️  Continuons sans cache optimisé\n";
    }
} else {
    echo "✅ Cache nettoyé\n";
}

// 5. Test connexion base de données
echo "🔌 Test de connexion à la base...\n";
exec('php bin/console doctrine:query:sql "SELECT 1" --env=prod 2>&1', $output, $return);
if ($return === 0) {
    echo "✅ Connexion base de données OK\n";
    
    // 6. Migrations de la base de données
    echo "🗄️  Mise à jour de la base de données...\n";
    exec('php bin/console doctrine:migrations:migrate --no-interaction --env=prod 2>&1', $output, $return);
    if ($return === 0) {
        echo "✅ Base de données mise à jour\n";
    } else {
        echo "⚠️  Migrations: " . implode("\n", array_slice($output, -3)) . "\n";
    }
} else {
    echo "❌ Problème de connexion base de données:\n";
    echo "   Vérifiez votre .env.prod.local\n";
}

// 7. Permissions finales
echo "🔐 Configuration des permissions...\n";
$dirs = ['var', 'var/cache', 'var/log', 'public/uploads'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        chmod($dir, 0755);
    }
}
echo "✅ Permissions configurées\n\n";

echo "🎉 Déploiement terminé!\n\n";
echo "📝 Prochaines étapes:\n";
echo "   1. Allez sur votre-domaine.com/admin-setup.php\n";
echo "   2. Supprimez admin-setup.php après utilisation\n";
echo "   3. Connectez-vous à /admin pour configurer le site\n\n";

// 8. Vérifications finales
echo "🔍 Vérifications:\n";
$checks = [
    'vendor/autoload.php' => 'Autoloader Composer',
    'public/index.php' => 'Point d\'entrée',
    'var/cache' => 'Dossier cache',
    'public/uploads' => 'Dossier uploads',
    'public/manifest.json' => 'Manifest PWA',
    'public/sw.js' => 'Service Worker',
    'public/js/push-notifications.js' => 'Script notifications push'
];

foreach ($checks as $file => $desc) {
    if (file_exists($file)) {
        echo "   ✅ $desc\n";
    } else {
        echo "   ❌ $desc manquant\n";
    }
}

// 9. Vérifications spécifiques PWA
echo "\n📱 Vérifications PWA:\n";
if (file_exists('public/manifest.json')) {
    $manifest = json_decode(file_get_contents('public/manifest.json'), true);
    if (isset($manifest['name'])) {
        echo "   ✅ Manifest valide: {$manifest['name']}\n";
    } else {
        echo "   ⚠️  Manifest invalide\n";
    }
}

if (file_exists('public/sw.js')) {
    $swContent = file_get_contents('public/sw.js');
    if (strpos($swContent, 'push') !== false) {
        echo "   ✅ Service Worker avec support push\n";
    } else {
        echo "   ⚠️  Service Worker sans support push\n";
    }
}

// 10. Rappel migrations PWA
echo "\n📊 Migrations PWA:\n";
echo "   Les tables suivantes doivent exister:\n";
echo "   - push_subscriptions (abonnements notifications)\n";
echo "   - notification_history (historique notifications)\n";
echo "   Si les migrations ont échoué, relancez: php bin/console doctrine:migrations:migrate --env=prod\n";

echo "\n✨ Checklist post-déploiement:\n";
echo "   □ Tester les notifications push sur /profile\n";
echo "   □ Vérifier que le service worker s'installe (DevTools > Application)\n";
echo "   □ Créer un événement test et vérifier les notifications\n";
echo "   □ Vérifier les logs: tail -f var/log/prod.log\n";
?>