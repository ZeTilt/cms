<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing User fields for production compatibility
 */
final class Version20250916110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing User fields (status, active, emailVerified) for production compatibility';
    }

    public function up(Schema $schema): void
    {
        // Add status field to users table if it doesn't exist
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "users" AND COLUMN_NAME = "status")');
        $this->addSql('SET @sqlstmt := IF(@exist = 0, "ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT \'pending\'", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        // Add active field to users table if it doesn't exist
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "users" AND COLUMN_NAME = "active")');
        $this->addSql('SET @sqlstmt := IF(@exist = 0, "ALTER TABLE users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        // Add emailVerified field to users table if it doesn't exist
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "users" AND COLUMN_NAME = "email_verified")');
        $this->addSql('SET @sqlstmt := IF(@exist = 0, "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        // Add emailVerificationToken field to users table if it doesn't exist
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "users" AND COLUMN_NAME = "email_verification_token")');
        $this->addSql('SET @sqlstmt := IF(@exist = 0, "ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(100) DEFAULT NULL", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        // Add createdAt field to users table if it doesn't exist
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "users" AND COLUMN_NAME = "created_at")');
        $this->addSql('SET @sqlstmt := IF(@exist = 0, "ALTER TABLE users ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT \'(DC2Type:datetime_immutable)\'", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        
        // Add updatedAt field to users table if it doesn't exist
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "users" AND COLUMN_NAME = "updated_at")');
        $this->addSql('SET @sqlstmt := IF(@exist = 0, "ALTER TABLE users ADD COLUMN updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'", "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }

    public function down(Schema $schema): void
    {
        // Remove the added fields
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS status');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS active');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS email_verified');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS email_verification_token');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS created_at');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS updated_at');
    }
}