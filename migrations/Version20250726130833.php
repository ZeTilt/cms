<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250726130833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table system_settings pour stocker les paramètres de configuration du système';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE system_settings (setting_key VARCHAR(100) NOT NULL, setting_value CLOB DEFAULT NULL, setting_type VARCHAR(50) DEFAULT NULL, description CLOB DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(setting_key))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE system_settings');
    }
}
