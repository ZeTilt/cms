<?php
// force-deploy.php - Forcer le déploiement même si connexion BDD échoue

echo "🚀 Déploiement forcé du site...\n\n";

echo "🗑️  Nettoyage complet du cache...\n";
// Supprimer tout le cache
exec('rm -rf var/cache/* 2>/dev/null || true');
exec('rm -rf var/log/* 2>/dev/null || true');

// Recréer les dossiers
$dirs = ['var/cache', 'var/log', 'var/cache/prod', 'public/uploads'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "   ✅ Dossier $dir créé\n";
    }
}

echo "🔧 Génération du cache Symfony...\n";
exec('APP_ENV=prod php bin/console cache:clear --no-debug 2>&1', $output, $return);
if ($return === 0) {
    echo "✅ Cache Symfony généré\n";
} else {
    echo "⚠️  Erreur cache, continuons...\n";
}

echo "🗄️  Création des tables (force)...\n";
exec('APP_ENV=prod php bin/console doctrine:schema:create --no-interaction 2>&1', $output, $return);
if ($return === 0) {
    echo "✅ Tables créées\n";
} else {
    echo "🔄 Tentative mise à jour...\n";
    exec('APP_ENV=prod php bin/console doctrine:schema:update --force --no-interaction 2>&1', $output2, $return2);
    if ($return2 === 0) {
        echo "✅ Tables mises à jour\n";
    } else {
        echo "⚠️  Problème avec les tables, on continue...\n";
    }
}

echo "👤 Création utilisateur admin...\n";
exec('APP_ENV=prod php bin/console app:create-admin 2>&1', $output, $return);
if ($return === 0) {
    echo "✅ Admin créé: admin@venetes.fr / admin123\n";
} else {
    echo "⚠️  Admin: " . implode("\n", array_slice($output, -2)) . "\n";
}

echo "⚙️  Configuration du site...\n";
exec('APP_ENV=prod php bin/console app:init-site-config 2>&1', $output, $return);
if ($return === 0) {
    echo "✅ Configuration initialisée\n";
} else {
    echo "⚠️  Config: continuons...\n";
}

echo "📄 Création des pages...\n";
exec('APP_ENV=prod php bin/console app:create-plongee-pages 2>&1', $output, $return);
if ($return === 0) {
    echo "✅ Pages créées\n";
} else {
    echo "⚠️  Pages: continuons...\n";
}

echo "🔐 Permissions finales...\n";
chmod('var', 0777);
chmod('var/cache', 0777);
chmod('var/log', 0777);
chmod('public/uploads', 0777);
echo "✅ Permissions configurées\n";

echo "\n🎉 Déploiement forcé terminé!\n\n";
echo "🌐 Testez maintenant: https://venetes.dhuicque.fr/\n";
echo "🔑 Admin: https://venetes.dhuicque.fr/admin\n";
echo "   Login: admin@venetes.fr\n";
echo "   Password: admin123\n\n";
echo "📝 Supprimez ce fichier après utilisation\n";
?>