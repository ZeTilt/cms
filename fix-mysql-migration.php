<?php

// Script de correction des migrations pour MySQL
echo "🔧 CORRECTION MIGRATIONS MYSQL\n";
echo "==============================\n\n";

// Configuration
$host = 'localhost';
$user = 'empo8897_venetes_preprod';
$pass = 'Vén3t3sPréPr0d';
$dbname = 'empo8897_venetes_preprod';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Connexion base de données OK\n\n";
    
    // 1. Créer table entity_attributes si manquante
    echo "1. Vérification table entity_attributes...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'entity_attributes'");
    if ($stmt->rowCount() == 0) {
        echo "   Création table entity_attributes...\n";
        $sql = "CREATE TABLE entity_attributes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT NOT NULL,
            attribute_name VARCHAR(100) NOT NULL,
            attribute_value TEXT DEFAULT NULL,
            attribute_type VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX idx_entity_lookup (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $pdo->exec($sql);
        echo "   ✅ Table entity_attributes créée\n";
    } else {
        echo "   ✅ Table entity_attributes existe\n";
    }
    
    // 2. Créer table modules si manquante  
    echo "2. Vérification table modules...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'modules'");
    if ($stmt->rowCount() == 0) {
        echo "   Création table modules...\n";
        $sql = "CREATE TABLE modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            display_name VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            active BOOLEAN NOT NULL,
            config JSON NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $pdo->exec($sql);
        echo "   ✅ Table modules créée\n";
    } else {
        echo "   ✅ Table modules existe\n";
    }
    
    // 3. Vérifier colonnes users
    echo "3. Vérification colonnes table users...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Ajouter colonne status si manquante
    if (!in_array('status', $columns)) {
        echo "   Ajout colonne status...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'approved'");
        echo "   ✅ Colonne status ajoutée\n";
    } else {
        echo "   ✅ Colonne status existe\n";
    }
    
    // Ajouter colonnes email verification si manquantes
    if (!in_array('email_verified', $columns)) {
        echo "   Ajout colonne email_verified...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN email_verified BOOLEAN NOT NULL DEFAULT 1");
        echo "   ✅ Colonne email_verified ajoutée\n";
    } else {
        echo "   ✅ Colonne email_verified existe\n";
    }
    
    if (!in_array('email_verification_token', $columns)) {
        echo "   Ajout colonne email_verification_token...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(100) DEFAULT NULL");
        echo "   ✅ Colonne email_verification_token ajoutée\n";
    } else {
        echo "   ✅ Colonne email_verification_token existe\n";
    }
    
    // 4. Créer table diving_levels si manquante
    echo "4. Vérification table diving_levels...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'diving_levels'");
    if ($stmt->rowCount() == 0) {
        echo "   Création table diving_levels...\n";
        $sql = "CREATE TABLE diving_levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20) NOT NULL UNIQUE,
            description TEXT DEFAULT NULL,
            sort_order INT DEFAULT 0,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $pdo->exec($sql);
        
        // Insérer les niveaux de base
        $levels = [
            ['N1', 'N1 - Plongeur Encadré 20m', 'Niveau 1 FFESSM', 10],
            ['N2', 'N2 - Plongeur Autonome 20m / Encadré 40m', 'Niveau 2 FFESSM', 20],
            ['N3', 'N3 - Plongeur Autonome 60m', 'Niveau 3 FFESSM', 30],
            ['N4', 'N4 - Guide de Palanquée', 'Niveau 4 FFESSM', 40],
            ['N5', 'N5 - Directeur de Plongée', 'Niveau 5 FFESSM', 50],
            ['E1', 'E1 - Initiateur', 'Encadrement niveau 1', 60],
            ['E2', 'E2 - Moniteur Fédéral 1er Degré', 'Encadrement niveau 2', 70],
            ['E3', 'E3 - Moniteur Fédéral 2ème Degré', 'Encadrement niveau 3', 80],
            ['E4', 'E4 - Moniteur Fédéral 3ème Degré', 'Encadrement niveau 4', 90],
            ['RIFAP', 'RIFAP', 'Réactions et Intervention Face à un Accident de Plongée', 100]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO diving_levels (code, name, description, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
        foreach ($levels as $level) {
            $stmt->execute($level);
        }
        
        echo "   ✅ Table diving_levels créée avec " . count($levels) . " niveaux\n";
    } else {
        echo "   ✅ Table diving_levels existe\n";
    }
    
    // 5. Vérifier colonnes event si table existe
    echo "5. Vérification table event...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'event'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("DESCRIBE event");
        $eventColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $newEventColumns = [
            'min_diving_level' => "ALTER TABLE event ADD COLUMN min_diving_level VARCHAR(50) DEFAULT NULL",
            'min_age' => "ALTER TABLE event ADD COLUMN min_age INT DEFAULT NULL", 
            'max_age' => "ALTER TABLE event ADD COLUMN max_age INT DEFAULT NULL",
            'requires_medical_certificate' => "ALTER TABLE event ADD COLUMN requires_medical_certificate BOOLEAN NOT NULL DEFAULT 0",
            'medical_certificate_validity_days' => "ALTER TABLE event ADD COLUMN medical_certificate_validity_days INT DEFAULT NULL",
            'requires_swimming_test' => "ALTER TABLE event ADD COLUMN requires_swimming_test BOOLEAN NOT NULL DEFAULT 0",
            'additional_requirements' => "ALTER TABLE event ADD COLUMN additional_requirements TEXT DEFAULT NULL"
        ];
        
        foreach ($newEventColumns as $col => $sql) {
            if (!in_array($col, $eventColumns)) {
                echo "   Ajout colonne $col...\n";
                $pdo->exec($sql);
                echo "   ✅ Colonne $col ajoutée\n";
            } else {
                echo "   ✅ Colonne $col existe\n";
            }
        }
    } else {
        echo "   ⚠️ Table event non trouvée\n";
    }
    
    // 6. Créer table event_participation si manquante
    echo "6. Vérification table event_participation...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'event_participation'");
    if ($stmt->rowCount() == 0) {
        echo "   Création table event_participation...\n";
        $sql = "CREATE TABLE event_participation (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            participant_id INT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'registered',
            registration_date DATETIME NOT NULL,
            confirmation_date DATETIME DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            FOREIGN KEY (event_id) REFERENCES event(id) ON DELETE CASCADE,
            FOREIGN KEY (participant_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_participation (event_id, participant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $pdo->exec($sql);
        echo "   ✅ Table event_participation créée\n";
    } else {
        echo "   ✅ Table event_participation existe\n";
    }
    
    // 7. Mettre à jour table doctrine_migration_versions
    echo "7. Mise à jour migrations...\n";
    $migrations = [
        'DoctrineMigrations\\Version20250915090117',
        'DoctrineMigrations\\Version20250915160418', 
        'DoctrineMigrations\\Version20250915161006'
    ];
    
    foreach ($migrations as $migration) {
        $stmt = $pdo->prepare("SELECT 1 FROM doctrine_migration_versions WHERE version = ?");
        $stmt->execute([$migration]);
        if ($stmt->rowCount() == 0) {
            $stmt = $pdo->prepare("INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES (?, NOW(), 1)");
            $stmt->execute([$migration]);
            echo "   ✅ Migration $migration marquée comme exécutée\n";
        } else {
            echo "   ✅ Migration $migration déjà marquée\n";
        }
    }
    
    echo "\n🎉 CORRECTION TERMINÉE AVEC SUCCÈS !\n\n";
    echo "📋 Résumé des actions :\n";
    echo "✅ Tables créées/vérifiées : entity_attributes, modules, diving_levels, event_participation\n"; 
    echo "✅ Colonnes ajoutées : users.status, users.email_verified, users.email_verification_token\n";
    echo "✅ Colonnes event : conditions d'inscription ajoutées\n";
    echo "✅ Migrations marquées comme exécutées\n\n";
    echo "🔧 Prochaines étapes :\n";
    echo "1. php bin/console app:create-admin-user --env=prod\n";
    echo "2. Tester l'application web\n";
    echo "3. Créer des événements avec conditions\n";

} catch (PDOException $e) {
    echo "❌ Erreur de base de données : " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Erreur générale : " . $e->getMessage() . "\n";
    exit(1);
}