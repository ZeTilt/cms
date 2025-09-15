<?php

echo "🔍 Test de connexion MySQL\n";
echo "==========================\n\n";

// Configuration depuis .env.prod.local
$host = 'localhost';
$user = 'empo8897_venetes_preprod';
$pass = 'Vén3t3sPréPr0d';
$dbname = 'empo8897_venetes_preprod';

echo "📋 Configuration testée :\n";
echo "   Host: $host\n";
echo "   User: $user\n";
echo "   Pass: " . str_repeat('*', strlen($pass)) . "\n";
echo "   DB: $dbname\n\n";

// Test 1: Connexion au serveur MySQL (sans base spécifique)
echo "1. Test connexion serveur MySQL...\n";
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    echo "   ✅ Connexion serveur MySQL OK\n\n";
    
    // Test 2: Lister les bases disponibles
    echo "2. Bases de données disponibles :\n";
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($databases as $db) {
        $marker = ($db === $dbname) ? ' ← CIBLE' : '';
        echo "   - $db$marker\n";
    }
    echo "\n";
    
    // Test 3: Connexion à la base spécifique
    echo "3. Test connexion base '$dbname'...\n";
    if (in_array($dbname, $databases)) {
        $pdo->exec("USE `$dbname`");
        echo "   ✅ Connexion base '$dbname' OK\n\n";
        
        // Test 4: Lister les tables
        echo "4. Tables existantes :\n";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "   ⚠️  Aucune table trouvée - base vide\n";
        } else {
            foreach ($tables as $table) {
                echo "   - $table\n";
            }
        }
        echo "\n";
        
        // Test 5: Vérifier les migrations
        if (in_array('doctrine_migration_versions', $tables)) {
            echo "5. Migrations appliquées :\n";
            $stmt = $pdo->query("SELECT version, executed_at FROM doctrine_migration_versions ORDER BY executed_at DESC LIMIT 10");
            $migrations = $stmt->fetchAll();
            foreach ($migrations as $migration) {
                echo "   - {$migration['version']} ({$migration['executed_at']})\n";
            }
        } else {
            echo "5. ⚠️  Table doctrine_migration_versions non trouvée\n";
            echo "   💡 Besoin d'initialiser Doctrine migrations\n";
        }
        
    } else {
        echo "   ❌ Base '$dbname' non trouvée\n";
        echo "   💡 Créer la base avec: CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    }
    
} catch (PDOException $e) {
    echo "   ❌ Erreur de connexion: " . $e->getMessage() . "\n";
    echo "\n🔧 Solutions possibles :\n";
    echo "1. Vérifier les identifiants dans le panneau O2switch\n";
    echo "2. Créer la base de données si elle n'existe pas\n";
    echo "3. Vérifier que l'utilisateur a les droits sur la base\n";
    echo "4. Tester depuis un autre script PHP sur le serveur\n";
}

echo "\n📋 Prochaines étapes si la connexion fonctionne :\n";
echo "1. php bin/console doctrine:migrations:migrate --env=prod\n";
echo "2. php bin/console app:fix-database --env=prod\n";
echo "3. php bin/console app:create-admin-user --env=prod\n";