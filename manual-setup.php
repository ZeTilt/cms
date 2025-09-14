<?php
// manual-setup.php - Configuration manuelle complète

echo "<h1>🛠️ Configuration manuelle du site</h1>";

// Test de base
try {
    require_once 'vendor/autoload.php';
    echo "<p style='color:green'>✅ Autoloader chargé</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Problème autoloader: " . $e->getMessage() . "</p>";
    exit;
}

// Connexion BDD directe
echo "<h2>🗄️ Configuration base de données</h2>";
$host = 'localhost';
$database = 'empo8897_venetes_preprod';
$username = 'empo8897_venetes_preprod';
$password = 'Vén3t3sPréPr0d';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $pdo->exec("USE `$database`");
    echo "<p style='color:green'>✅ Connexion BDD OK</p>";
    
    // Créer les tables manuellement
    echo "<h3>📋 Création des tables...</h3>";
    
    // Table doctrine_migration_versions
    $pdo->exec("CREATE TABLE IF NOT EXISTS doctrine_migration_versions (
        version VARCHAR(191) NOT NULL PRIMARY KEY,
        executed_at DATETIME NULL,
        execution_time INT NULL
    )");
    echo "<p>✅ Table doctrine_migration_versions</p>";
    
    // Table site_config
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        config_key VARCHAR(255) NOT NULL UNIQUE,
        config_value TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Table site_config</p>";
    
    // Table user
    $pdo->exec("CREATE TABLE IF NOT EXISTS user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(180) NOT NULL UNIQUE,
        roles JSON NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Table user</p>";
    
    // Table event_type
    $pdo->exec("CREATE TABLE IF NOT EXISTS event_type (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        code VARCHAR(255) NOT NULL UNIQUE,
        color VARCHAR(7) NOT NULL,
        description TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Table event_type</p>";
    
    // Table event
    $pdo->exec("CREATE TABLE IF NOT EXISTS event (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type_id INT,
        parent_event_id INT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        start_date DATETIME NOT NULL,
        end_date DATETIME,
        location VARCHAR(255),
        status VARCHAR(20) DEFAULT 'active',
        max_participants INT,
        current_participants INT DEFAULT 0,
        color VARCHAR(7),
        is_recurring TINYINT(1) DEFAULT 0,
        recurrence_type VARCHAR(50),
        recurrence_interval INT,
        recurrence_weekdays JSON,
        recurrence_end_date DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_type_id) REFERENCES event_type(id),
        FOREIGN KEY (parent_event_id) REFERENCES event(id) ON DELETE CASCADE
    )");
    echo "<p>✅ Table event</p>";
    
    // Autres tables nécessaires
    $basic_tables = [
        "article" => "CREATE TABLE IF NOT EXISTS article (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content LONGTEXT,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "page" => "CREATE TABLE IF NOT EXISTS page (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content LONGTEXT,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($basic_tables as $table => $sql) {
        $pdo->exec($sql);
        echo "<p>✅ Table $table</p>";
    }
    
    echo "<h3>👤 Création utilisateur admin</h3>";
    
    // Vérifier si admin existe
    $stmt = $pdo->prepare("SELECT id FROM user WHERE email = 'admin@venetes.fr'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        // Créer l'admin
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO user (email, password, roles, name) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'admin@venetes.fr',
            $password_hash,
            '["ROLE_SUPER_ADMIN", "ROLE_ADMIN"]',
            'Administrateur'
        ]);
        echo "<p style='color:green'>✅ Utilisateur admin créé</p>";
        echo "<p><strong>Email:</strong> admin@venetes.fr</p>";
        echo "<p><strong>Mot de passe:</strong> admin123</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ Utilisateur admin existe déjà</p>";
    }
    
    echo "<h3>⚙️ Configuration du site</h3>";
    
    $configs = [
        'club_name' => 'Club Subaquatique des Vénètes',
        'club_address' => '5 Av. du Président Wilson, 56000 Vannes',
        'club_phone' => '02 97 00 00 00',
        'club_email' => 'contact@venetes.fr',
        'club_facebook' => 'https://www.facebook.com/plongeevenetes/',
        'site_description' => 'Club de plongée sous-marine à Vannes'
    ];
    
    foreach ($configs as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO site_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    echo "<p style='color:green'>✅ Configuration du site initialisée</p>";
    
    echo "<h3>🎨 Types d'événements par défaut</h3>";
    
    $event_types = [
        ['Formation', 'formation', '#3B82F6', 'Formations et brevets'],
        ['Plongée', 'plongee', '#FD7E29', 'Sorties plongée'],
        ['Sortie', 'sortie', '#10B981', 'Sorties et excursions'],
        ['Réunion', 'reunion', '#8B5CF6', 'Réunions du club'],
        ['Maintenance', 'maintenance', '#F59E0B', 'Maintenance matériel'],
        ['Événement', 'evenement', '#EF4444', 'Événements spéciaux']
    ];
    
    foreach ($event_types as $type) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO event_type (name, code, color, description) VALUES (?, ?, ?, ?)");
        $stmt->execute($type);
    }
    echo "<p style='color:green'>✅ Types d'événements créés</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Erreur BDD: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🎉 Configuration terminée !</h2>";
echo "<div style='background:#e8f5e8; padding:15px; border:1px solid #4CAF50; margin:20px 0;'>";
echo "<h3>🌐 Testez votre site :</h3>";
echo "<p><strong>Site public:</strong> <a href='https://venetes.dhuicque.fr/' target='_blank'>https://venetes.dhuicque.fr/</a></p>";
echo "<p><strong>Administration:</strong> <a href='https://venetes.dhuicque.fr/admin' target='_blank'>https://venetes.dhuicque.fr/admin</a></p>";
echo "<p><strong>Email:</strong> admin@venetes.fr</p>";
echo "<p><strong>Mot de passe:</strong> admin123</p>";
echo "</div>";

echo "<div style='background:#fff3cd; padding:10px; border:1px solid #ffc107; margin:10px 0;'>";
echo "<strong>⚠️ SÉCURITÉ:</strong> Supprimez ce fichier maintenant !";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
h1 { color: #2c5aa0; }
h2 { color: #fd7e29; border-bottom: 2px solid #fd7e29; padding-bottom: 5px; }
h3 { color: #333; }
</style>