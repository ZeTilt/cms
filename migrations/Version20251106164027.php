<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106164027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add quantity field to event_participation table with default value of 1';
    }

    public function up(Schema $schema): void
    {
        // Add quantity column with default value of 1
        $this->addSql('ALTER TABLE event_participation ADD quantity INT NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_participation DROP quantity');
    }
}
