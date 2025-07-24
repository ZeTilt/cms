<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250724092829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE business_contacts (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, assigned_to_id INTEGER NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, company VARCHAR(255) DEFAULT NULL, position VARCHAR(100) DEFAULT NULL, status VARCHAR(50) NOT NULL, source VARCHAR(50) NOT NULL, notes CLOB DEFAULT NULL, tags CLOB NOT NULL --(DC2Type:json)
        , address CLOB DEFAULT NULL --(DC2Type:json)
        , custom_fields CLOB DEFAULT NULL --(DC2Type:json)
        , last_contact_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , next_follow_up_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_3B701BE5F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3B701BE5E7927C74 ON business_contacts (email)');
        $this->addSql('CREATE INDEX IDX_3B701BE5F4BD7827 ON business_contacts (assigned_to_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE business_contacts');
    }
}
