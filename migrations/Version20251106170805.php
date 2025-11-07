<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106170805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add participation_type field to event_participation table (instructor or autonomous)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diving_levels CHANGE is_instructor is_instructor TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE event_participation ADD participation_type VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diving_levels CHANGE is_instructor is_instructor TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE event_participation DROP participation_type');
    }
}
