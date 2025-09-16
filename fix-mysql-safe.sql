-- Script de correction MySQL SÉCURISÉ - ignore les erreurs si colonnes/tables existent
-- Exécuter avec: mysql -u empo8897_venetes_preprod -p empo8897_venetes_preprod < fix-mysql-safe.sql

-- 1. Marquer la migration problématique comme exécutée
INSERT IGNORE INTO doctrine_migration_versions (version, executed_at, execution_time) 
VALUES ('DoctrineMigrations\\Version20250722091640', NOW(), 1);

-- 2. Ajouter colonnes users (ignore les erreurs si elles existent)
-- Colonnes status
SET @sql = 'ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT ''approved''';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='status' AND TABLE_SCHEMA=DATABASE()) = 0, @sql, 'SELECT "Column status already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Colonne email_verified
SET @sql = 'ALTER TABLE users ADD COLUMN email_verified BOOLEAN NOT NULL DEFAULT 1';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='email_verified' AND TABLE_SCHEMA=DATABASE()) = 0, @sql, 'SELECT "Column email_verified already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Colonne email_verification_token
SET @sql = 'ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(100) DEFAULT NULL';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='email_verification_token' AND TABLE_SCHEMA=DATABASE()) = 0, @sql, 'SELECT "Column email_verification_token already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Ajouter colonnes event pour les conditions
-- min_diving_level
SET @sql = 'ALTER TABLE event ADD COLUMN min_diving_level VARCHAR(50) DEFAULT NULL';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='event' AND COLUMN_NAME='min_diving_level' AND TABLE_SCHEMA=DATABASE()) = 0, @sql, 'SELECT "Column min_diving_level already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- min_age
SET @sql = 'ALTER TABLE event ADD COLUMN min_age INTEGER DEFAULT NULL';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='event' AND COLUMN_NAME='min_age' AND TABLE_SCHEMA=DATABASE()) = 0, @sql, 'SELECT "Column min_age already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- max_age
SET @sql = 'ALTER TABLE event ADD COLUMN max_age INTEGER DEFAULT NULL';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='event' AND COLUMN_NAME='max_age' AND TABLE_SCHEMA=DATABASE()) = 0, @sql, 'SELECT "Column max_age already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- requires_medical_certificate
SET @sql = 'ALTER TABLE event ADD COLUMN requires_medical_certificate BOOLEAN NOT NULL DEFAULT 0';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='event' AND COLUMN_NAME='requires_medical_certificate' AND TABLE_SCHEMA=DATABASE()) = 0, @sql, 'SELECT "Column requires_medical_certificate already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- medical_certificate_validity_days
SET @sql = 'ALTER TABLE event ADD COLUMN medical_certificate_validity_days INTEGER DEFAULT NULL';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='event' AND COLUMN_NAME='medical_certificate_validity_days' AND TABLE_SCHEMA=DATABASE()) = 0, @sql, 'SELECT "Column medical_certificate_validity_days already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- requires_swimming_test
SET @sql = 'ALTER TABLE event ADD COLUMN requires_swimming_test BOOLEAN NOT NULL DEFAULT 0';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='event' AND COLUMN_NAME='requires_swimming_test' AND TABLE_SCHEMA=DATABASE()) = 0, @sql, 'SELECT "Column requires_swimming_test already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- additional_requirements
SET @sql = 'ALTER TABLE event ADD COLUMN additional_requirements TEXT DEFAULT NULL';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='event' AND COLUMN_NAME='additional_requirements' AND TABLE_SCHEMA=DATABASE()) = 0, @sql, 'SELECT "Column additional_requirements already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Créer les tables manquantes
CREATE TABLE IF NOT EXISTS entity_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    attribute_name VARCHAR(100) NOT NULL,
    attribute_value TEXT DEFAULT NULL,
    attribute_type VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    INDEX idx_entity_lookup (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    active BOOLEAN NOT NULL,
    config JSON NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS diving_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_participation (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Insérer les niveaux de plongée
INSERT IGNORE INTO diving_levels (code, name, description, sort_order, is_active, created_at) VALUES
('N1', 'N1 - Plongeur Encadré 20m', 'Niveau 1 FFESSM', 10, 1, NOW()),
('N2', 'N2 - Plongeur Autonome 20m / Encadré 40m', 'Niveau 2 FFESSM', 20, 1, NOW()),
('N3', 'N3 - Plongeur Autonome 60m', 'Niveau 3 FFESSM', 30, 1, NOW()),
('N4', 'N4 - Guide de Palanquée', 'Niveau 4 FFESSM', 40, 1, NOW()),
('N5', 'N5 - Directeur de Plongée', 'Niveau 5 FFESSM', 50, 1, NOW()),
('E1', 'E1 - Initiateur', 'Encadrement niveau 1', 60, 1, NOW()),
('E2', 'E2 - Moniteur Fédéral 1er Degré', 'Encadrement niveau 2', 70, 1, NOW()),
('E3', 'E3 - Moniteur Fédéral 2ème Degré', 'Encadrement niveau 3', 80, 1, NOW()),
('E4', 'E4 - Moniteur Fédéral 3ème Degré', 'Encadrement niveau 4', 90, 1, NOW()),
('RIFAP', 'RIFAP', 'Réactions et Intervention Face à un Accident de Plongée', 100, 1, NOW());

-- 6. Marquer les migrations comme exécutées
INSERT IGNORE INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES
('DoctrineMigrations\\Version20250915090117', NOW(), 1),
('DoctrineMigrations\\Version20250915160418', NOW(), 1),
('DoctrineMigrations\\Version20250915161006', NOW(), 1);

-- 7. Affichage final
SELECT '✅ CORRECTION TERMINÉE' as Status;
SELECT CONCAT('Tables: ', COUNT(*)) as TablesCount FROM information_schema.tables WHERE table_schema = DATABASE();
SELECT CONCAT('Migrations: ', COUNT(*)) as MigrationsCount FROM doctrine_migration_versions;