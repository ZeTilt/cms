<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250731113936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events ADD COLUMN required_level VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE events ADD COLUMN minimum_depth INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE events ADD COLUMN maximum_depth INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE events ADD COLUMN requires_medical_certificate BOOLEAN DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE events ADD COLUMN requires_insurance BOOLEAN DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__events AS SELECT id, organizer_id, pilot_id, title, slug, description, short_description, start_date, end_date, location, address, status, type, tags, featured_image, meta_data, max_participants, current_participants, requires_registration, is_recurring, recurring_config, created_at, updated_at, club_departure_time, dock_departure_time, diving_comments FROM events');
        $this->addSql('DROP TABLE events');
        $this->addSql('CREATE TABLE events (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, organizer_id INTEGER NOT NULL, pilot_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, short_description CLOB DEFAULT NULL, start_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , end_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , location VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, type VARCHAR(50) NOT NULL, tags CLOB NOT NULL --(DC2Type:json)
        , featured_image VARCHAR(255) DEFAULT NULL, meta_data CLOB DEFAULT NULL --(DC2Type:json)
        , max_participants INTEGER DEFAULT NULL, current_participants INTEGER NOT NULL, requires_registration BOOLEAN NOT NULL, is_recurring BOOLEAN NOT NULL, recurring_config CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , club_departure_time DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , dock_departure_time DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , diving_comments CLOB DEFAULT NULL, CONSTRAINT FK_5387574A876C4DDA FOREIGN KEY (organizer_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5387574ACE55439B FOREIGN KEY (pilot_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO events (id, organizer_id, pilot_id, title, slug, description, short_description, start_date, end_date, location, address, status, type, tags, featured_image, meta_data, max_participants, current_participants, requires_registration, is_recurring, recurring_config, created_at, updated_at, club_departure_time, dock_departure_time, diving_comments) SELECT id, organizer_id, pilot_id, title, slug, description, short_description, start_date, end_date, location, address, status, type, tags, featured_image, meta_data, max_participants, current_participants, requires_registration, is_recurring, recurring_config, created_at, updated_at, club_departure_time, dock_departure_time, diving_comments FROM __temp__events');
        $this->addSql('DROP TABLE __temp__events');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5387574A989D9B62 ON events (slug)');
        $this->addSql('CREATE INDEX IDX_5387574A876C4DDA ON events (organizer_id)');
        $this->addSql('CREATE INDEX IDX_5387574ACE55439B ON events (pilot_id)');
    }
}
