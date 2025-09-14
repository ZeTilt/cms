<?php
// quick-test.php - Test rapide pour voir ce qui bloque

echo "=== TEST RAPIDE ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n\n";

// Test 1: Symfony basic
echo "1. Test Symfony basic...\n";
if (file_exists('vendor/autoload.php')) {
    echo "   ✅ Autoloader existe\n";
    
    try {
        require_once 'vendor/autoload.php';
        echo "   ✅ Autoloader chargé\n";
        
        // Test simple sans BD
        if (class_exists('Symfony\Component\Console\Application')) {
            echo "   ✅ Classes Symfony Console OK\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    }
}

// Test 2: Environnement
echo "\n2. Test environnement...\n";
$_SERVER['APP_ENV'] = 'prod';
putenv('APP_ENV=prod');
echo "   ✅ APP_ENV=prod défini\n";

// Test 3: Base de données simple
echo "\n3. Test BDD simple...\n";
try {
    $pdo = new PDO(
        "mysql:host=localhost;charset=utf8mb4", 
        "empo8897_venetes_preprod", 
        "Vén3t3sPréPr0d"
    );
    echo "   ✅ Connexion MySQL OK\n";
    
    $pdo->exec("USE empo8897_venetes_preprod");
    echo "   ✅ Base accessible\n";
    
    // Test création table simple
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100))");
    echo "   ✅ Peut créer des tables\n";
    
    $pdo->exec("DROP TABLE test_table");
    echo "   ✅ Test nettoyé\n";
    
} catch (PDOException $e) {
    echo "   ❌ Erreur BDD: " . $e->getMessage() . "\n";
}

// Test 4: Permissions
echo "\n4. Test permissions...\n";
$dirs = ['var', 'var/cache', 'var/log'];
foreach ($dirs as $dir) {
    if (is_writable($dir)) {
        echo "   ✅ $dir writable\n";
    } else {
        echo "   ❌ $dir pas writable\n";
    }
}

// Test 5: Mémoire/Timeout
echo "\n5. Limites système...\n";
echo "   Memory limit: " . ini_get('memory_limit') . "\n";
echo "   Max execution time: " . ini_get('max_execution_time') . "s\n";
echo "   PHP SAPI: " . php_sapi_name() . "\n";

echo "\n=== FIN TEST ===\n";
?>