<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250731093204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__articles AS SELECT id, author_id, title, slug, content, excerpt, featured_image, status, created_at, updated_at, published_at, meta_data, category, tags FROM articles');
        $this->addSql('DROP TABLE articles');
        $this->addSql('CREATE TABLE articles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content CLOB NOT NULL, excerpt CLOB DEFAULT NULL, featured_image VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, published_at DATETIME DEFAULT NULL, meta_data CLOB DEFAULT NULL --(DC2Type:json)
        , category VARCHAR(255) DEFAULT NULL, tags CLOB DEFAULT NULL --(DC2Type:json)
        , CONSTRAINT FK_BFDD3168F675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO articles (id, author_id, title, slug, content, excerpt, featured_image, status, created_at, updated_at, published_at, meta_data, category, tags) SELECT id, author_id, title, slug, content, excerpt, featured_image, status, created_at, updated_at, published_at, meta_data, category, tags FROM __temp__articles');
        $this->addSql('DROP TABLE __temp__articles');
        $this->addSql('CREATE INDEX IDX_BFDD3168F675F31B ON articles (author_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BFDD3168989D9B62 ON articles (slug)');
        $this->addSql('ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT \'pending_approval\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__articles AS SELECT id, author_id, title, slug, content, excerpt, featured_image, status, created_at, updated_at, published_at, tags, meta_data, category FROM articles');
        $this->addSql('DROP TABLE articles');
        $this->addSql('CREATE TABLE articles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content CLOB NOT NULL, excerpt CLOB DEFAULT NULL, featured_image VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, published_at DATETIME DEFAULT NULL, tags CLOB DEFAULT NULL, meta_data CLOB DEFAULT NULL --(DC2Type:json)
        , category VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_BFDD3168F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO articles (id, author_id, title, slug, content, excerpt, featured_image, status, created_at, updated_at, published_at, tags, meta_data, category) SELECT id, author_id, title, slug, content, excerpt, featured_image, status, created_at, updated_at, published_at, tags, meta_data, category FROM __temp__articles');
        $this->addSql('DROP TABLE __temp__articles');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BFDD3168989D9B62 ON articles (slug)');
        $this->addSql('CREATE INDEX IDX_BFDD3168F675F31B ON articles (author_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, user_type_id, email, roles, password, first_name, last_name, active, created_at, updated_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_type_id INTEGER DEFAULT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_1483A5E99D419299 FOREIGN KEY (user_type_id) REFERENCES user_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO users (id, user_type_id, email, roles, password, first_name, last_name, active, created_at, updated_at) SELECT id, user_type_id, email, roles, password, first_name, last_name, active, created_at, updated_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E99D419299 ON users (user_type_id)');
    }
}
