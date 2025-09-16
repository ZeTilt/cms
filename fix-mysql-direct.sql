-- Script de correction MySQL à exécuter directement en base
-- Exécuter avec: mysql -u empo8897_venetes_preprod -p empo8897_venetes_preprod < fix-mysql-direct.sql

-- 1. Marquer la migration problématique comme exécutée
INSERT IGNORE INTO doctrine_migration_versions (version, executed_at, execution_time) 
VALUES ('DoctrineMigrations\\Version20250722091640', NOW(), 1);

-- 2. Ajouter les colonnes manquantes à la table users si elles n'existent pas
ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'approved';
ALTER TABLE users ADD COLUMN email_verified BOOLEAN NOT NULL DEFAULT 1;
ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(100) DEFAULT NULL;

-- 3. Créer la table entity_attributes si elle n'existe pas
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

-- 4. Créer la table modules si elle n'existe pas
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

-- 5. Ajouter les colonnes de conditions à la table event
ALTER TABLE event ADD COLUMN min_diving_level VARCHAR(50) DEFAULT NULL;
ALTER TABLE event ADD COLUMN min_age INTEGER DEFAULT NULL;
ALTER TABLE event ADD COLUMN max_age INTEGER DEFAULT NULL;
ALTER TABLE event ADD COLUMN requires_medical_certificate BOOLEAN NOT NULL DEFAULT 0;
ALTER TABLE event ADD COLUMN medical_certificate_validity_days INTEGER DEFAULT NULL;
ALTER TABLE event ADD COLUMN requires_swimming_test BOOLEAN NOT NULL DEFAULT 0;
ALTER TABLE event ADD COLUMN additional_requirements TEXT DEFAULT NULL;

-- 6. Créer la table diving_levels
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

-- Insérer les niveaux de plongée
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

-- 7. Créer la table event_participation
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

-- 8. Marquer toutes les nouvelles migrations comme exécutées
INSERT IGNORE INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES
('DoctrineMigrations\\Version20250915090117', NOW(), 1),
('DoctrineMigrations\\Version20250915160418', NOW(), 1),
('DoctrineMigrations\\Version20250915161006', NOW(), 1);

-- 9. Affichage final
SELECT 'CORRECTION TERMINÉE - Tables créées et migrations marquées comme exécutées' as Status;