<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250801115242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_types (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, slug VARCHAR(50) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, active BOOLEAN NOT NULL, sort_order INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_182B381C989D9B62 ON event_types (slug)');
        
        // Insérer les types d'événements par défaut
        $now = date('Y-m-d H:i:s');
        $this->addSql("INSERT INTO event_types (slug, name, description, color, active, sort_order, created_at) VALUES 
            ('plongee', 'Plongée', 'Sorties et formations de plongée sous-marine', '#0066CC', 1, 1, '$now'),
            ('formation', 'Formation', 'Formations théoriques et pratiques', '#FF6600', 1, 2, '$now'),
            ('reunion', 'Réunion', 'Assemblées générales et réunions du club', '#666666', 1, 3, '$now'),
            ('evenement', 'Événement', 'Événements spéciaux et manifestations', '#00CC66', 1, 4, '$now')");
        $this->addSql('CREATE TEMPORARY TABLE __temp__gallery AS SELECT id, author_id, title, slug, description, cover_image, visibility, access_code, created_at, updated_at, metadata, duration_days, end_date FROM gallery');
        $this->addSql('DROP TABLE gallery');
        $this->addSql('CREATE TABLE gallery (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, visibility VARCHAR(20) DEFAULT \'public\' NOT NULL, access_code VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , metadata CLOB DEFAULT NULL --(DC2Type:json)
        , duration_days INTEGER DEFAULT NULL, end_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_472B783AF675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO gallery (id, author_id, title, slug, description, cover_image, visibility, access_code, created_at, updated_at, metadata, duration_days, end_date) SELECT id, author_id, title, slug, description, cover_image, visibility, access_code, created_at, updated_at, metadata, duration_days, end_date FROM __temp__gallery');
        $this->addSql('DROP TABLE __temp__gallery');
        $this->addSql('CREATE INDEX IDX_472B783AF675F31B ON gallery (author_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_472B783A989D9B62 ON gallery (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE event_types');
        $this->addSql('CREATE TEMPORARY TABLE __temp__gallery AS SELECT id, author_id, title, slug, description, cover_image, visibility, access_code, created_at, updated_at, metadata, duration_days, end_date FROM gallery');
        $this->addSql('DROP TABLE gallery');
        $this->addSql('CREATE TABLE gallery (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, visibility VARCHAR(20) DEFAULT \'public\' NOT NULL, access_code VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , metadata CLOB DEFAULT NULL --(DC2Type:json)
        , duration_days INTEGER DEFAULT NULL, end_date DATETIME DEFAULT NULL, CONSTRAINT FK_472B783AF675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO gallery (id, author_id, title, slug, description, cover_image, visibility, access_code, created_at, updated_at, metadata, duration_days, end_date) SELECT id, author_id, title, slug, description, cover_image, visibility, access_code, created_at, updated_at, metadata, duration_days, end_date FROM __temp__gallery');
        $this->addSql('DROP TABLE __temp__gallery');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_472B783A989D9B62 ON gallery (slug)');
        $this->addSql('CREATE INDEX IDX_472B783AF675F31B ON gallery (author_id)');
    }
}
