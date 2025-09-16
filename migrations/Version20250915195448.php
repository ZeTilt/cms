<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915195448 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Remove hardcoded condition fields from event table - event_condition table already exists
        // Check if columns exist before dropping them
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "event" AND COLUMN_NAME = "min_diving_level")');
        $this->addSql('SET @sqlstmt := IF(@exist > 0, "ALTER TABLE event DROP COLUMN min_diving_level", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "event" AND COLUMN_NAME = "min_age")');
        $this->addSql('SET @sqlstmt := IF(@exist > 0, "ALTER TABLE event DROP COLUMN min_age", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "event" AND COLUMN_NAME = "max_age")');
        $this->addSql('SET @sqlstmt := IF(@exist > 0, "ALTER TABLE event DROP COLUMN max_age", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "event" AND COLUMN_NAME = "requires_medical_certificate")');
        $this->addSql('SET @sqlstmt := IF(@exist > 0, "ALTER TABLE event DROP COLUMN requires_medical_certificate", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "event" AND COLUMN_NAME = "medical_certificate_validity_days")');
        $this->addSql('SET @sqlstmt := IF(@exist > 0, "ALTER TABLE event DROP COLUMN medical_certificate_validity_days", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "event" AND COLUMN_NAME = "requires_swimming_test")');
        $this->addSql('SET @sqlstmt := IF(@exist > 0, "ALTER TABLE event DROP COLUMN requires_swimming_test", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "event" AND COLUMN_NAME = "additional_requirements")');
        $this->addSql('SET @sqlstmt := IF(@exist > 0, "ALTER TABLE event DROP COLUMN additional_requirements", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }

    public function down(Schema $schema): void
    {
        // Add back hardcoded condition fields to event table (event_condition table managed by separate migration)
        $this->addSql('ALTER TABLE event ADD COLUMN min_diving_level VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD COLUMN min_age INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD COLUMN max_age INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD COLUMN requires_medical_certificate BOOLEAN NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE event ADD COLUMN medical_certificate_validity_days INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD COLUMN requires_swimming_test BOOLEAN NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE event ADD COLUMN additional_requirements CLOB DEFAULT NULL');
    }
}
