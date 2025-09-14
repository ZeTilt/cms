<?php
// test-db.php - Test connexion base de données O2switch
echo "<h1>🔌 Test connexion base de données</h1>";

// Lire le fichier .env.prod.local
if (!file_exists('.env.prod.local')) {
    echo "<p style='color:red'>❌ Fichier .env.prod.local manquant!</p>";
    exit;
}

$env_content = file_get_contents('.env.prod.local');
echo "<h2>📋 Configuration trouvée:</h2>";

// Extraire les paramètres de DATABASE_URL
if (preg_match('/DATABASE_URL="mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/([^?"]+)/', $env_content, $matches)) {
    $username = $matches[1];
    $password = $matches[2];
    $host = $matches[3];
    $port = $matches[4];
    $database = $matches[5];
    
    echo "<p><strong>Host:</strong> $host</p>";
    echo "<p><strong>Port:</strong> $port</p>";
    echo "<p><strong>Database:</strong> $database</p>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>Password:</strong> " . str_repeat('*', strlen($password)) . "</p>";
    
    echo "<h2>🔗 Test de connexion...</h2>";
    
    try {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        echo "<p style='color:green'>✅ Connexion au serveur MySQL réussie</p>";
        
        // Tester l'accès à la base spécifique
        try {
            $pdo->exec("USE `$database`");
            echo "<p style='color:green'>✅ Accès à la base '$database' réussi</p>";
            
            // Lister les tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                echo "<p style='color:blue'>ℹ️ Base de données vide (normal pour première installation)</p>";
            } else {
                echo "<p style='color:blue'>ℹ️ Tables existantes: " . implode(', ', $tables) . "</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Erreur accès base '$database': " . $e->getMessage() . "</p>";
            echo "<p>🔧 Vérifiez que la base existe et que l'utilisateur a les permissions</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Erreur connexion MySQL: " . $e->getMessage() . "</p>";
        
        // Diagnostics spécifiques
        $error_code = $e->getCode();
        switch ($error_code) {
            case 1045:
                echo "<p>🔧 <strong>Solution:</strong> Vérifiez le nom d'utilisateur et mot de passe</p>";
                break;
            case 1049:
                echo "<p>🔧 <strong>Solution:</strong> La base de données n'existe pas. Créez-la via cPanel</p>";
                break;
            case 2002:
                echo "<p>🔧 <strong>Solution:</strong> Serveur MySQL inaccessible. Vérifiez le host</p>";
                break;
            default:
                echo "<p>🔧 Code d'erreur: $error_code</p>";
        }
    }
    
} else {
    echo "<p style='color:red'>❌ Format DATABASE_URL incorrect!</p>";
    echo "<p>Format attendu: mysql://user:password@host:port/database</p>";
    echo "<h3>Contenu actuel .env.prod.local:</h3>";
    echo "<pre>" . htmlspecialchars($env_content) . "</pre>";
}

echo "<hr>";
echo "<h2>🛠️ Paramètres O2switch typiques:</h2>";
echo "<ul>";
echo "<li><strong>Host:</strong> localhost (ou l'IP fournie par O2switch)</li>";
echo "<li><strong>Port:</strong> 3306</li>";
echo "<li><strong>Database:</strong> empo8897_venetes_preprod</li>";
echo "<li><strong>Username:</strong> empo8897_venetes_preprod</li>";
echo "<li><strong>Password:</strong> Vén3t3sPréPr0d</li>";
echo "</ul>";

echo "<p><strong>📝 Supprimez ce fichier après diagnostic !</strong></p>";
?>