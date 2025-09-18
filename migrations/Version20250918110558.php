<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250918110558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor Event table: remove type/color columns, enforce EventType relationship, add contact person';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP type, DROP color, CHANGE event_type_id event_type_id INT NOT NULL, CHANGE current_participants contact_person_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA74F8A983C FOREIGN KEY (contact_person_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA74F8A983C ON event (contact_person_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA74F8A983C');
        $this->addSql('DROP INDEX IDX_3BAE0AA74F8A983C ON event');
        $this->addSql('ALTER TABLE event ADD type VARCHAR(50) DEFAULT NULL, ADD color VARCHAR(7) DEFAULT NULL, CHANGE event_type_id event_type_id INT DEFAULT NULL, CHANGE contact_person_id current_participants INT DEFAULT NULL');
    }
}
