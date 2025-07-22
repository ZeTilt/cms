<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250722093601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, excerpt CLOB DEFAULT NULL, content CLOB NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, featured_image VARCHAR(255) DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description CLOB DEFAULT NULL, tags CLOB NOT NULL --(DC2Type:json)
        , published_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , sort_order INTEGER NOT NULL, CONSTRAINT FK_2074E575F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2074E575989D9B62 ON pages (slug)');
        $this->addSql('CREATE INDEX IDX_2074E575F675F31B ON pages (author_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE pages');
    }
}
