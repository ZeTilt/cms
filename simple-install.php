<?php
// simple-install.php - Installation ultra simple sans Symfony

echo "INSTALLATION SIMPLE\n";
echo "===================\n\n";

// 1. Créer BDD manuellement
echo "1. Création base de données...\n";

try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "empo8897_venetes_preprod", "Vén3t3sPréPr0d");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("USE empo8897_venetes_preprod");
    echo "   ✅ Connexion BDD OK\n";
    
    // Tables minimales pour que Symfony fonctionne
    $tables = [
        "CREATE TABLE IF NOT EXISTS user (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(180) NOT NULL UNIQUE,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(255) DEFAULT NULL
        )",
        "CREATE TABLE IF NOT EXISTS doctrine_migration_versions (
            version VARCHAR(191) NOT NULL PRIMARY KEY,
            executed_at DATETIME NULL,
            execution_time INT NULL
        )",
        "CREATE TABLE IF NOT EXISTS site_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            config_key VARCHAR(255) NOT NULL UNIQUE,
            config_value TEXT
        )"
    ];
    
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    echo "   ✅ Tables créées\n";
    
    // Admin
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO user (email, password, roles, name) VALUES 
        ('admin@venetes.fr', '$hash', '[\"ROLE_SUPER_ADMIN\"]', 'Admin')");
    echo "   ✅ Admin créé\n";
    
    // Config de base
    $configs = [
        "('club_name', 'Club Subaquatique des Vénètes')",
        "('club_address', '5 Av. du Président Wilson, 56000 Vannes')",
        "('club_email', 'contact@venetes.fr')"
    ];
    
    foreach ($configs as $config) {
        $pdo->exec("INSERT IGNORE INTO site_config (config_key, config_value) VALUES $config");
    }
    echo "   ✅ Configuration OK\n";
    
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Créer dossiers
echo "\n2. Création dossiers...\n";
$dirs = ['var/cache/prod', 'var/log', 'public/uploads'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    chmod($dir, 0777);
    echo "   ✅ $dir\n";
}

echo "\n3. Test du site...\n";
echo "   🌐 https://venetes.dhuicque.fr/\n";
echo "   🔑 https://venetes.dhuicque.fr/admin\n";
echo "   📧 admin@venetes.fr / admin123\n";

echo "\nINSTALLATION TERMINÉE!\n";
echo "Supprimez ce fichier maintenant.\n";
?>