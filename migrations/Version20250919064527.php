<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250919064527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD highest_diving_level_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9E5B84CE9 FOREIGN KEY (highest_diving_level_id) REFERENCES diving_levels (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E9E5B84CE9 ON users (highest_diving_level_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9E5B84CE9');
        $this->addSql('DROP INDEX IDX_1483A5E9E5B84CE9 ON users');
        $this->addSql('ALTER TABLE users DROP highest_diving_level_id');
    }
}
