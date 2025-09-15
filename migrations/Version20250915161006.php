<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915161006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_participation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_id INTEGER NOT NULL, participant_id INTEGER NOT NULL, status VARCHAR(20) NOT NULL, registration_date DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , confirmation_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , notes CLOB DEFAULT NULL, CONSTRAINT FK_8F0C52E371F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8F0C52E39D1C3019 FOREIGN KEY (participant_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8F0C52E371F7E88B ON event_participation (event_id)');
        $this->addSql('CREATE INDEX IDX_8F0C52E39D1C3019 ON event_participation (participant_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__event AS SELECT id, event_type_id, parent_event_id, title, description, start_date, end_date, location, type, status, max_participants, current_participants, color, created_at, updated_at, is_recurring, recurrence_type, recurrence_interval, recurrence_weekdays, recurrence_end_date, min_diving_level, min_age, max_age, requires_medical_certificate, medical_certificate_validity_days, requires_swimming_test, additional_requirements FROM event');
        $this->addSql('DROP TABLE event');
        $this->addSql('CREATE TABLE event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_type_id INTEGER DEFAULT NULL, parent_event_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, status VARCHAR(20) NOT NULL, max_participants INTEGER DEFAULT NULL, current_participants INTEGER DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , is_recurring BOOLEAN NOT NULL, recurrence_type VARCHAR(20) DEFAULT NULL, recurrence_interval INTEGER DEFAULT NULL, recurrence_weekdays CLOB DEFAULT NULL --(DC2Type:json)
        , recurrence_end_date DATE DEFAULT NULL, min_diving_level VARCHAR(50) DEFAULT NULL, min_age INTEGER DEFAULT NULL, max_age INTEGER DEFAULT NULL, requires_medical_certificate BOOLEAN NOT NULL, medical_certificate_validity_days INTEGER DEFAULT NULL, requires_swimming_test BOOLEAN NOT NULL, additional_requirements CLOB DEFAULT NULL, CONSTRAINT FK_3BAE0AA7401B253C FOREIGN KEY (event_type_id) REFERENCES event_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3BAE0AA7EE3A445A FOREIGN KEY (parent_event_id) REFERENCES event (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event (id, event_type_id, parent_event_id, title, description, start_date, end_date, location, type, status, max_participants, current_participants, color, created_at, updated_at, is_recurring, recurrence_type, recurrence_interval, recurrence_weekdays, recurrence_end_date, min_diving_level, min_age, max_age, requires_medical_certificate, medical_certificate_validity_days, requires_swimming_test, additional_requirements) SELECT id, event_type_id, parent_event_id, title, description, start_date, end_date, location, type, status, max_participants, current_participants, color, created_at, updated_at, is_recurring, recurrence_type, recurrence_interval, recurrence_weekdays, recurrence_end_date, min_diving_level, min_age, max_age, requires_medical_certificate, medical_certificate_validity_days, requires_swimming_test, additional_requirements FROM __temp__event');
        $this->addSql('DROP TABLE __temp__event');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7EE3A445A ON event (parent_event_id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7401B253C ON event (event_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE event_participation');
        $this->addSql('CREATE TEMPORARY TABLE __temp__event AS SELECT id, event_type_id, parent_event_id, title, description, start_date, end_date, location, type, status, max_participants, current_participants, color, created_at, updated_at, is_recurring, recurrence_type, recurrence_interval, recurrence_weekdays, recurrence_end_date, min_diving_level, min_age, max_age, requires_medical_certificate, medical_certificate_validity_days, requires_swimming_test, additional_requirements FROM event');
        $this->addSql('DROP TABLE event');
        $this->addSql('CREATE TABLE event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_type_id INTEGER DEFAULT NULL, parent_event_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, status VARCHAR(20) NOT NULL, max_participants INTEGER DEFAULT NULL, current_participants INTEGER DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , is_recurring BOOLEAN NOT NULL, recurrence_type VARCHAR(20) DEFAULT NULL, recurrence_interval INTEGER DEFAULT NULL, recurrence_weekdays CLOB DEFAULT NULL --(DC2Type:json)
        , recurrence_end_date DATE DEFAULT NULL, min_diving_level VARCHAR(50) DEFAULT NULL, min_age INTEGER DEFAULT NULL, max_age INTEGER DEFAULT NULL, requires_medical_certificate BOOLEAN DEFAULT 0 NOT NULL, medical_certificate_validity_days INTEGER DEFAULT NULL, requires_swimming_test BOOLEAN DEFAULT 0 NOT NULL, additional_requirements CLOB DEFAULT NULL, CONSTRAINT FK_3BAE0AA7401B253C FOREIGN KEY (event_type_id) REFERENCES event_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3BAE0AA7EE3A445A FOREIGN KEY (parent_event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event (id, event_type_id, parent_event_id, title, description, start_date, end_date, location, type, status, max_participants, current_participants, color, created_at, updated_at, is_recurring, recurrence_type, recurrence_interval, recurrence_weekdays, recurrence_end_date, min_diving_level, min_age, max_age, requires_medical_certificate, medical_certificate_validity_days, requires_swimming_test, additional_requirements) SELECT id, event_type_id, parent_event_id, title, description, start_date, end_date, location, type, status, max_participants, current_participants, color, created_at, updated_at, is_recurring, recurrence_type, recurrence_interval, recurrence_weekdays, recurrence_end_date, min_diving_level, min_age, max_age, requires_medical_certificate, medical_certificate_validity_days, requires_swimming_test, additional_requirements FROM __temp__event');
        $this->addSql('DROP TABLE __temp__event');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7401B253C ON event (event_type_id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7EE3A445A ON event (parent_event_id)');
    }
}
