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
        $this->addSql('CREATE TEMPORARY TABLE __temp__event AS SELECT id, event_type_id, parent_event_id, title, description, start_date, end_date, location, type, status, max_participants, current_participants, color, created_at, updated_at, is_recurring, recurrence_type, recurrence_interval, recurrence_weekdays, recurrence_end_date FROM event');
        $this->addSql('DROP TABLE event');
        $this->addSql('CREATE TABLE event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_type_id INTEGER DEFAULT NULL, parent_event_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, status VARCHAR(20) NOT NULL, max_participants INTEGER DEFAULT NULL, current_participants INTEGER DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , is_recurring BOOLEAN NOT NULL, recurrence_type VARCHAR(20) DEFAULT NULL, recurrence_interval INTEGER DEFAULT NULL, recurrence_weekdays CLOB DEFAULT NULL --(DC2Type:json)
        , recurrence_end_date DATE DEFAULT NULL, CONSTRAINT FK_3BAE0AA7401B253C FOREIGN KEY (event_type_id) REFERENCES event_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3BAE0AA7EE3A445A FOREIGN KEY (parent_event_id) REFERENCES event (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event (id, event_type_id, parent_event_id, title, description, start_date, end_date, location, type, status, max_participants, current_participants, color, created_at, updated_at, is_recurring, recurrence_type, recurrence_interval, recurrence_weekdays, recurrence_end_date) SELECT id, event_type_id, parent_event_id, title, description, start_date, end_date, location, type, status, max_participants, current_participants, color, created_at, updated_at, is_recurring, recurrence_type, recurrence_interval, recurrence_weekdays, recurrence_end_date FROM __temp__event');
        $this->addSql('DROP TABLE __temp__event');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7401B253C ON event (event_type_id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7EE3A445A ON event (parent_event_id)');
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
