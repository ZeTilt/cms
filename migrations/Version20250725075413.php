<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250725075413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE testimonials (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, related_service_id INTEGER DEFAULT NULL, related_event_id INTEGER DEFAULT NULL, submitted_by_id INTEGER DEFAULT NULL, client_name VARCHAR(255) NOT NULL, client_title VARCHAR(255) DEFAULT NULL, client_company VARCHAR(255) DEFAULT NULL, client_email VARCHAR(255) DEFAULT NULL, content CLOB NOT NULL, short_content CLOB DEFAULT NULL, rating INTEGER DEFAULT NULL, status VARCHAR(20) NOT NULL, category VARCHAR(50) DEFAULT NULL, project_name VARCHAR(255) DEFAULT NULL, project_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , client_photo VARCHAR(255) DEFAULT NULL, project_image VARCHAR(255) DEFAULT NULL, tags CLOB DEFAULT NULL --(DC2Type:json)
        , featured BOOLEAN NOT NULL, allow_public_display BOOLEAN NOT NULL, display_order INTEGER DEFAULT NULL, metadata CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , approved_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_383115796791A156 FOREIGN KEY (related_service_id) REFERENCES services (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_38311579D774A626 FOREIGN KEY (related_event_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3831157979F7D87D FOREIGN KEY (submitted_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_383115796791A156 ON testimonials (related_service_id)');
        $this->addSql('CREATE INDEX IDX_38311579D774A626 ON testimonials (related_event_id)');
        $this->addSql('CREATE INDEX IDX_3831157979F7D87D ON testimonials (submitted_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE testimonials');
    }
}
