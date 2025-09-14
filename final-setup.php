<?php
// final-setup.php - Setup final maintenant que Symfony fonctionne

echo "🚀 SETUP FINAL\n";
echo "===============\n\n";

// 1. Exécuter les migrations avec les bons paramètres
echo "1. Migrations base de données...\n";
exec('php bin/console doctrine:migrations:migrate --no-interaction --env=prod 2>&1', $output, $return);

if ($return === 0) {
    echo "   ✅ Migrations exécutées\n";
    foreach ($output as $line) {
        if (strpos($line, 'Migration') !== false || strpos($line, 'migrated') !== false) {
            echo "   $line\n";
        }
    }
} else {
    echo "   ⚠️  Sortie migrations:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }
}

// 2. Créer l'admin
echo "\n2. Création utilisateur admin...\n";
unset($output);
exec('php bin/console app:create-admin --env=prod 2>&1', $output, $return);

if ($return === 0) {
    echo "   ✅ Admin créé\n";
} else {
    echo "   ⚠️  Sortie admin:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }
}

// 3. Configuration du site
echo "\n3. Configuration du site...\n";
unset($output);
exec('php bin/console app:init-site-config --env=prod 2>&1', $output, $return);

if ($return === 0) {
    echo "   ✅ Configuration initialisée\n";
} else {
    echo "   ⚠️  Sortie config:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }
}

// 4. Créer les pages
echo "\n4. Création des pages par défaut...\n";
unset($output);
exec('php bin/console app:create-plongee-pages --env=prod 2>&1', $output, $return);

if ($return === 0) {
    echo "   ✅ Pages créées\n";
} else {
    echo "   ⚠️  Sortie pages:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }
}

// 5. Vérifications finales
echo "\n5. Vérifications finales...\n";

// Test BDD
try {
    $pdo = new PDO("mysql:host=localhost;dbname=empo8897_venetes_preprod;charset=utf8mb4", 
                   "empo8897_venetes_preprod", "Vén3t3sPréPr0d");
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "   📋 Tables: " . implode(', ', $tables) . "\n";
    
    if (in_array('user', $tables)) {
        $userCount = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
        echo "   👤 Utilisateurs: $userCount\n";
    }
    
    if (in_array('site_config', $tables)) {
        $configCount = $pdo->query("SELECT COUNT(*) FROM site_config")->fetchColumn();
        echo "   ⚙️  Configurations: $configCount\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Erreur vérification: " . $e->getMessage() . "\n";
}

echo "\n🎉 INSTALLATION TERMINÉE !\n";
echo "================================\n\n";

echo "🌐 Site public: https://venetes.dhuicque.fr/\n";
echo "🔑 Administration: https://venetes.dhuicque.fr/admin\n";
echo "📧 Email: admin@venetes.fr\n";
echo "🔐 Mot de passe: admin123\n\n";

echo "📝 IMPORTANT: Supprimez ce fichier maintenant !\n";
echo "   rm final-setup.php\n";
?>