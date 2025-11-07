<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Suppression des tables du système EAV (Entity-Attribute-Value)
 *
 * IMPORTANT : Exécuter la commande app:migrate-eav-to-columns AVANT cette migration !
 */
final class Version20251106120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression des tables du système EAV (attribute_definitions et entity_attributes)';
    }

    public function up(Schema $schema): void
    {
        // Supprimer les tables EAV
        $this->addSql('DROP TABLE IF EXISTS entity_attributes');
        $this->addSql('DROP TABLE IF EXISTS attribute_definitions');
    }

    public function down(Schema $schema): void
    {
        // Recréer les tables EAV si rollback nécessaire
        $this->addSql('CREATE TABLE attribute_definitions (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            label VARCHAR(200) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            field_type VARCHAR(20) NOT NULL,
            options JSON DEFAULT NULL,
            required TINYINT(1) NOT NULL,
            active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE entity_attributes (
            id INT AUTO_INCREMENT NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT NOT NULL,
            attribute_name VARCHAR(100) NOT NULL,
            attribute_value LONGTEXT DEFAULT NULL,
            attribute_type VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_entity_lookup (entity_type, entity_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }
}
