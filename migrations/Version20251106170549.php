<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106170549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_instructor field to diving_levels table';
    }

    public function up(Schema $schema): void
    {
        // Add is_instructor column with default value FALSE
        $this->addSql('ALTER TABLE diving_levels ADD is_instructor TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE event_participation CHANGE quantity quantity INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diving_levels DROP is_instructor');
        $this->addSql('ALTER TABLE event_participation CHANGE quantity quantity INT DEFAULT 1 NOT NULL');
    }
}
