<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing datetime columns to user_types and user_type_attributes
 */
final class Version20250724094000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing datetime columns to user_types and user_type_attributes';
    }

    public function up(Schema $schema): void
    {
        // Add datetime columns to user_types
        $this->addSql('ALTER TABLE user_types ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user_types ADD COLUMN updated_at DATETIME DEFAULT NULL');

        // Add datetime columns to user_type_attributes
        $this->addSql('ALTER TABLE user_type_attributes ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user_type_attributes ADD COLUMN updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // SQLite doesn't support DROP COLUMN, would need table recreation
        // For simplicity, we'll leave the columns in the down migration
    }
}