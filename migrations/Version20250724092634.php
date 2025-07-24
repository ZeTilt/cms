<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250724092634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE events (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, organizer_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, short_description CLOB DEFAULT NULL, start_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , end_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , location VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, type VARCHAR(50) NOT NULL, tags CLOB NOT NULL --(DC2Type:json)
        , featured_image VARCHAR(255) DEFAULT NULL, meta_data CLOB DEFAULT NULL --(DC2Type:json)
        , max_participants INTEGER DEFAULT NULL, current_participants INTEGER NOT NULL, requires_registration BOOLEAN NOT NULL, is_recurring BOOLEAN NOT NULL, recurring_config CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5387574A876C4DDA FOREIGN KEY (organizer_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5387574A989D9B62 ON events (slug)');
        $this->addSql('CREATE INDEX IDX_5387574A876C4DDA ON events (organizer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE events');
    }
}
