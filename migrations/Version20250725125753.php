<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250725125753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE certifications (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, level VARCHAR(50) NOT NULL, issuing_organization VARCHAR(100) DEFAULT NULL, certification_type VARCHAR(50) NOT NULL, validity_duration_months INTEGER DEFAULT NULL, prerequisites CLOB DEFAULT NULL --(DC2Type:json)
        , competencies CLOB DEFAULT NULL --(DC2Type:json)
        , color VARCHAR(255) DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, is_active BOOLEAN NOT NULL, requires_renewal BOOLEAN NOT NULL, allow_self_certification BOOLEAN NOT NULL, display_order INTEGER DEFAULT NULL, metadata CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE TABLE user_certifications (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, certification_id INTEGER NOT NULL, obtained_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , expires_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , status VARCHAR(50) NOT NULL, certificate_number VARCHAR(100) DEFAULT NULL, issuing_authority VARCHAR(255) DEFAULT NULL, notes CLOB DEFAULT NULL, metadata CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5088A98CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5088A98CCB47068A FOREIGN KEY (certification_id) REFERENCES certifications (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5088A98CA76ED395 ON user_certifications (user_id)');
        $this->addSql('CREATE INDEX IDX_5088A98CCB47068A ON user_certifications (certification_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE certifications');
        $this->addSql('DROP TABLE user_certifications');
    }
}
