<?php
// migrate-only.php - Forcer uniquement les migrations

echo "MIGRATIONS SYMFONY\n";
echo "==================\n\n";

// 1. Vérifications de base
echo "1. Vérifications...\n";

if (!file_exists('vendor/autoload.php')) {
    echo "❌ vendor/autoload.php manquant\n";
    exit(1);
}

if (!file_exists('.env.prod.local')) {
    echo "❌ .env.prod.local manquant\n";
    exit(1);
}

echo "   ✅ Fichiers OK\n";

// 2. Variables d'environnement
echo "\n2. Configuration environnement...\n";
$_SERVER['APP_ENV'] = 'prod';
putenv('APP_ENV=prod');
echo "   ✅ APP_ENV=prod\n";

// 3. Nettoyer le cache manuellement
echo "\n3. Nettoyage cache...\n";
$cache_dirs = ['var/cache/prod', 'var/cache/dev'];
foreach ($cache_dirs as $dir) {
    if (is_dir($dir)) {
        exec("rm -rf $dir/* 2>/dev/null");
        echo "   ✅ $dir nettoyé\n";
    }
}

// 4. Test BDD avant migration
echo "\n4. Test connexion BDD...\n";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=empo8897_venetes_preprod;charset=utf8mb4", 
                   "empo8897_venetes_preprod", "Vén3t3sPréPr0d");
    echo "   ✅ Connexion BDD OK\n";
} catch (PDOException $e) {
    echo "   ❌ Erreur BDD: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Lancer les migrations avec timeout et verbosité
echo "\n5. Exécution migrations...\n";

// Timeout plus court et sortie forcée
ini_set('max_execution_time', 30);

$commands = [
    'php bin/console doctrine:migrations:migrate --no-interaction --env=prod -v',
    'php bin/console app:create-admin --env=prod -v',
    'php bin/console app:init-site-config --env=prod -v'
];

foreach ($commands as $cmd) {
    echo "\n   Exécution: $cmd\n";
    echo "   ";
    
    $start_time = time();
    $process = popen($cmd . ' 2>&1', 'r');
    
    if ($process) {
        // Lire la sortie avec timeout
        while (!feof($process) && (time() - $start_time) < 30) {
            $line = fgets($process, 1024);
            if ($line !== false) {
                echo $line;
                flush();
            } else {
                usleep(100000); // 100ms
            }
        }
        
        $return_code = pclose($process);
        
        if (time() - $start_time >= 30) {
            echo "\n   ⚠️  Timeout après 30s\n";
        } elseif ($return_code === 0) {
            echo "\n   ✅ OK\n";
        } else {
            echo "\n   ❌ Erreur (code $return_code)\n";
        }
    } else {
        echo "❌ Impossible d'exécuter\n";
    }
}

echo "\n6. Vérification finale...\n";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Tables créées: " . implode(', ', $tables) . "\n";
    
    if (in_array('user', $tables)) {
        $count = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
        echo "   Utilisateurs: $count\n";
    }
} catch (Exception $e) {
    echo "   ❌ Vérification: " . $e->getMessage() . "\n";
}

echo "\nMIGRATIONS TERMINÉES!\n";
echo "Testez: https://venetes.dhuicque.fr/admin\n";
?>