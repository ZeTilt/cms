<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250730090443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE permissions (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, display_name VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, module VARCHAR(50) NOT NULL, "action" VARCHAR(50) NOT NULL, active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DEDCC6F5E237E06 ON permissions (name)');
        $this->addSql('CREATE TABLE role_permissions (role_id INTEGER NOT NULL, permission_id INTEGER NOT NULL, PRIMARY KEY(role_id, permission_id), CONSTRAINT FK_1FBA94E6D60322AC FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1FBA94E6FED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1FBA94E6D60322AC ON role_permissions (role_id)');
        $this->addSql('CREATE INDEX IDX_1FBA94E6FED90CCA ON role_permissions (permission_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__roles AS SELECT id, name, display_name, description, hierarchy, active, created_at, updated_at FROM roles');
        $this->addSql('DROP TABLE roles');
        $this->addSql('CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, display_name VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, hierarchy INTEGER NOT NULL, active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO roles (id, name, display_name, description, hierarchy, active, created_at, updated_at) SELECT id, name, display_name, description, hierarchy, active, created_at, updated_at FROM __temp__roles');
        $this->addSql('DROP TABLE __temp__roles');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B63E2EC75E237E06 ON roles (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE role_permissions');
        $this->addSql('ALTER TABLE roles ADD COLUMN permissions CLOB NOT NULL');
    }
}
