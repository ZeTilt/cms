<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250731160850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add duration_days and end_date fields to Gallery entity for expiration functionality';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gallery ADD COLUMN duration_days INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE gallery ADD COLUMN end_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__gallery AS SELECT id, author_id, title, slug, description, cover_image, visibility, access_code, created_at, updated_at, metadata FROM gallery');
        $this->addSql('DROP TABLE gallery');
        $this->addSql('CREATE TABLE gallery (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, visibility VARCHAR(20) DEFAULT \'public\' NOT NULL, access_code VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , metadata CLOB DEFAULT NULL --(DC2Type:json)
        , CONSTRAINT FK_472B783AF675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO gallery (id, author_id, title, slug, description, cover_image, visibility, access_code, created_at, updated_at, metadata) SELECT id, author_id, title, slug, description, cover_image, visibility, access_code, created_at, updated_at, metadata FROM __temp__gallery');
        $this->addSql('DROP TABLE __temp__gallery');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_472B783A989D9B62 ON gallery (slug)');
        $this->addSql('CREATE INDEX IDX_472B783AF675F31B ON gallery (author_id)');
    }
}
