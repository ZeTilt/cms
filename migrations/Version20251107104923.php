<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107104923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD pilot_id INT DEFAULT NULL, ADD needs_pilot TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7CE55439B FOREIGN KEY (pilot_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7CE55439B ON event (pilot_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7CE55439B');
        $this->addSql('DROP INDEX IDX_3BAE0AA7CE55439B ON event');
        $this->addSql('ALTER TABLE event DROP pilot_id, DROP needs_pilot');
    }
}
