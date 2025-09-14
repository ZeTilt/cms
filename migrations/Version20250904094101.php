<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250904094101 extends AbstractMigration
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
        $this->addSql('CREATE TEMPORARY TABLE __temp__modules AS SELECT id, name, display_name, description, active, config, created_at, updated_at FROM modules');
        $this->addSql('DROP TABLE modules');
        $this->addSql('CREATE TABLE modules (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, display_name VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, active BOOLEAN NOT NULL, config CLOB NOT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO modules (id, name, display_name, description, active, config, created_at, updated_at) SELECT id, name, display_name, description, active, config, created_at, updated_at FROM __temp__modules');
        $this->addSql('DROP TABLE __temp__modules');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2EB743D75E237E06 ON modules (name)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__pages AS SELECT id, author_id, title, slug, excerpt, type, status, featured_image, meta_title, meta_description, tags, published_at, created_at, updated_at, sort_order, template_path FROM pages');
        $this->addSql('DROP TABLE pages');
        $this->addSql('CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, excerpt CLOB DEFAULT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, featured_image VARCHAR(255) DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description CLOB DEFAULT NULL, tags CLOB NOT NULL --(DC2Type:json)
        , published_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , sort_order INTEGER NOT NULL, template_path VARCHAR(255) NOT NULL, content CLOB DEFAULT NULL, CONSTRAINT FK_2074E575F675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO pages (id, author_id, title, slug, excerpt, type, status, featured_image, meta_title, meta_description, tags, published_at, created_at, updated_at, sort_order, template_path) SELECT id, author_id, title, slug, excerpt, type, status, featured_image, meta_title, meta_description, tags, published_at, created_at, updated_at, sort_order, template_path FROM __temp__pages');
        $this->addSql('DROP TABLE __temp__pages');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2074E575989D9B62 ON pages (slug)');
        $this->addSql('CREATE INDEX IDX_2074E575F675F31B ON pages (author_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, roles, password, first_name, last_name, active, created_at, updated_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO users (id, email, roles, password, first_name, last_name, active, created_at, updated_at) SELECT id, email, roles, password, first_name, last_name, active, created_at, updated_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__articles AS SELECT id, author_id, title, slug, content, excerpt, featured_image, status, created_at, updated_at, published_at, meta_data, category, tags FROM articles');
        $this->addSql('DROP TABLE articles');
        $this->addSql('CREATE TABLE articles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content CLOB NOT NULL, excerpt CLOB DEFAULT NULL, featured_image VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, published_at DATETIME DEFAULT NULL, meta_data CLOB DEFAULT NULL --(DC2Type:json)
        , category VARCHAR(255) DEFAULT NULL, tags CLOB DEFAULT NULL --(DC2Type:json)
        , CONSTRAINT FK_BFDD3168F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO articles (id, author_id, title, slug, content, excerpt, featured_image, status, created_at, updated_at, published_at, meta_data, category, tags) SELECT id, author_id, title, slug, content, excerpt, featured_image, status, created_at, updated_at, published_at, meta_data, category, tags FROM __temp__articles');
        $this->addSql('DROP TABLE __temp__articles');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BFDD3168989D9B62 ON articles (slug)');
        $this->addSql('CREATE INDEX IDX_BFDD3168F675F31B ON articles (author_id)');
        $this->addSql('CREATE INDEX idx_articles_author ON articles (author_id)');
        $this->addSql('CREATE INDEX idx_articles_category ON articles (category)');
        $this->addSql('CREATE INDEX idx_articles_status_published ON articles (status, published_at)');
        $this->addSql('CREATE INDEX idx_articles_published_at ON articles (published_at)');
        $this->addSql('CREATE INDEX idx_articles_status ON articles (status)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__modules AS SELECT id, name, display_name, description, active, config, created_at, updated_at FROM modules');
        $this->addSql('DROP TABLE modules');
        $this->addSql('CREATE TABLE modules (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, display_name VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, active BOOLEAN NOT NULL, config CLOB NOT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO modules (id, name, display_name, description, active, config, created_at, updated_at) SELECT id, name, display_name, description, active, config, created_at, updated_at FROM __temp__modules');
        $this->addSql('DROP TABLE __temp__modules');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2EB743D75E237E06 ON modules (name)');
        $this->addSql('CREATE INDEX idx_modules_active ON modules (active)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__pages AS SELECT id, author_id, title, slug, excerpt, template_path, type, status, featured_image, meta_title, meta_description, tags, published_at, created_at, updated_at, sort_order FROM pages');
        $this->addSql('DROP TABLE pages');
        $this->addSql('CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, excerpt CLOB DEFAULT NULL, template_path VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, featured_image VARCHAR(255) DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description CLOB DEFAULT NULL, tags CLOB NOT NULL --(DC2Type:json)
        , published_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , sort_order INTEGER NOT NULL, CONSTRAINT FK_2074E575F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO pages (id, author_id, title, slug, excerpt, template_path, type, status, featured_image, meta_title, meta_description, tags, published_at, created_at, updated_at, sort_order) SELECT id, author_id, title, slug, excerpt, template_path, type, status, featured_image, meta_title, meta_description, tags, published_at, created_at, updated_at, sort_order FROM __temp__pages');
        $this->addSql('DROP TABLE __temp__pages');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2074E575989D9B62 ON pages (slug)');
        $this->addSql('CREATE INDEX IDX_2074E575F675F31B ON pages (author_id)');
        $this->addSql('CREATE INDEX idx_pages_status_published ON pages (status, published_at)');
        $this->addSql('CREATE INDEX idx_pages_published_at ON pages (published_at)');
        $this->addSql('CREATE INDEX idx_pages_type ON pages (type)');
        $this->addSql('CREATE INDEX idx_pages_status ON pages (status)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, roles, password, first_name, last_name, active, created_at, updated_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO users (id, email, roles, password, first_name, last_name, active, created_at, updated_at) SELECT id, email, roles, password, first_name, last_name, active, created_at, updated_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX idx_users_roles ON users (roles)');
    }
}
