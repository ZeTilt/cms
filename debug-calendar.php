<?php
// debug-calendar.php - Debug erreur 500 calendrier

echo "<h1>🔍 Debug Calendrier</h1>";

try {
    require_once 'vendor/autoload.php';
    
    use Symfony\Component\Dotenv\Dotenv;
    
    $dotenv = new Dotenv();
    $dotenv->loadEnv('.env');
    
    echo "<h2>1. Test connexion BDD</h2>";
    $pdo = new PDO("mysql:host=localhost;dbname=empo8897_venetes_preprod;charset=utf8mb4", 
                   "empo8897_venetes_preprod", "Vén3t3sPréPr0d");
    echo "<p style='color:green'>✅ Connexion BDD OK</p>";
    
    echo "<h2>2. Vérification tables</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $required_tables = ['event_type', 'event', 'user'];
    foreach ($required_tables as $table) {
        if (in_array($table, $tables)) {
            echo "<p style='color:green'>✅ Table $table existe</p>";
        } else {
            echo "<p style='color:red'>❌ Table $table manquante</p>";
        }
    }
    
    echo "<h2>3. Test EventType</h2>";
    if (in_array('event_type', $tables)) {
        $count = $pdo->query("SELECT COUNT(*) FROM event_type")->fetchColumn();
        echo "<p>📊 Nombre de types d'événements: $count</p>";
        
        if ($count > 0) {
            $types = $pdo->query("SELECT name, color, is_active FROM event_type LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            echo "<h4>Types existants:</h4>";
            foreach ($types as $type) {
                $status = $type['is_active'] ? '🟢' : '🔴';
                echo "<p>{$status} {$type['name']} ({$type['color']})</p>";
            }
        }
        
        // Test requête findActive
        echo "<h4>Test requête findActive:</h4>";
        $activeTypes = $pdo->query("SELECT * FROM event_type WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Types actifs trouvés: " . count($activeTypes) . "</p>";
        
    } else {
        echo "<p style='color:red'>❌ Table event_type manquante - il faut créer les tables</p>";
    }
    
    echo "<h2>4. Test Symfony Kernel</h2>";
    $_ENV['APP_ENV'] = 'prod';
    $_SERVER['APP_ENV'] = 'prod';
    
    if (class_exists('App\Kernel')) {
        $kernel = new \App\Kernel('prod', false);
        $kernel->boot();
        echo "<p style='color:green'>✅ Kernel Symfony OK</p>";
        
        $container = $kernel->getContainer();
        
        // Test EventTypeRepository
        if ($container->has('App\Repository\EventTypeRepository')) {
            echo "<p style='color:green'>✅ EventTypeRepository disponible</p>";
            
            try {
                $eventTypeRepo = $container->get('App\Repository\EventTypeRepository');
                $activeTypes = $eventTypeRepo->findActive();
                echo "<p style='color:green'>✅ Méthode findActive() fonctionne</p>";
                echo "<p>Types actifs via repository: " . count($activeTypes) . "</p>";
            } catch (Exception $e) {
                echo "<p style='color:red'>❌ Erreur EventTypeRepository: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color:red'>❌ EventTypeRepository non disponible</p>";
        }
        
    } else {
        echo "<p style='color:red'>❌ Kernel Symfony non disponible</p>";
    }
    
    echo "<h2>5. Test CalendarController</h2>";
    if (class_exists('App\Controller\CalendarController')) {
        echo "<p style='color:green'>✅ CalendarController existe</p>";
    } else {
        echo "<p style='color:red'>❌ CalendarController manquant</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Erreur fatale</h2>";
    echo "<p>Message: " . $e->getMessage() . "</p>";
    echo "<p>Fichier: " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>📝 Pour résoudre l'erreur 500:</strong></p>";
echo "<ol>";
echo "<li>Vérifiez si les tables event_type existent</li>";
echo "<li>Si manquantes, exécutez les migrations: php bin/console doctrine:migrations:migrate</li>";
echo "<li>Créez des types d'événements via l'admin</li>";
echo "<li>Supprimez ce fichier après diagnostic</li>";
echo "</ol>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
h1 { color: #2c5aa0; }
h2 { color: #fd7e29; border-bottom: 2px solid #fd7e29; padding-bottom: 5px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>