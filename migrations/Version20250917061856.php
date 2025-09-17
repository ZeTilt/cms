<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917061856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs - converted to MySQL
        $this->addSql('CREATE TABLE articles (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, excerpt LONGTEXT DEFAULT NULL, featured_image VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, published_at DATETIME DEFAULT NULL, meta_data JSON DEFAULT NULL, category VARCHAR(255) DEFAULT NULL, tags JSON DEFAULT NULL, INDEX IDX_BFDD3168F675F31B (author_id), UNIQUE INDEX UNIQ_BFDD3168989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE attribute_definitions (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, label VARCHAR(200) NOT NULL, entity_type VARCHAR(50) NOT NULL, field_type VARCHAR(20) NOT NULL, options JSON DEFAULT NULL, required TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE diving_levels (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(20) NOT NULL, description LONGTEXT DEFAULT NULL, sort_order INT NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_FA5AB8A977153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE entity_attributes (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT NOT NULL, attribute_name VARCHAR(100) NOT NULL, attribute_value LONGTEXT DEFAULT NULL, attribute_type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_entity_lookup (entity_type, entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, event_type_id INT DEFAULT NULL, parent_event_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, status VARCHAR(20) NOT NULL, max_participants INT DEFAULT NULL, current_participants INT DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_recurring TINYINT(1) NOT NULL, recurrence_type VARCHAR(20) DEFAULT NULL, recurrence_interval INT DEFAULT NULL, recurrence_weekdays JSON DEFAULT NULL, recurrence_end_date DATE DEFAULT NULL, INDEX IDX_3BAE0AA7401B253C (event_type_id), INDEX IDX_3BAE0AA7EE3A445A (parent_event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_condition (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, entity_class VARCHAR(100) NOT NULL, attribute_name VARCHAR(100) NOT NULL, operator VARCHAR(20) NOT NULL, value LONGTEXT DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3B43B67E71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_participation (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, participant_id INT NOT NULL, status VARCHAR(20) NOT NULL, registration_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', confirmation_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', notes LONGTEXT DEFAULT NULL, INDEX IDX_8F0C52E371F7E88B (event_id), INDEX IDX_8F0C52E39D1C3019 (participant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(50) NOT NULL, color VARCHAR(7) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_93151B8277153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE gallery (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, visibility VARCHAR(20) DEFAULT \'public\' NOT NULL, access_code VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', metadata JSON DEFAULT NULL, INDEX IDX_472B783AF675F31B (author_id), UNIQUE INDEX UNIQ_472B783A989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, gallery_id INT NOT NULL, uploaded_by_id INT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INT NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, alt VARCHAR(255) DEFAULT NULL, caption LONGTEXT DEFAULT NULL, position INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', exif_data JSON DEFAULT NULL, INDEX IDX_C53D045F4E7AF8F (gallery_id), INDEX IDX_C53D045FA2B28FE8 (uploaded_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE modules (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, display_name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, active TINYINT(1) NOT NULL, config JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_2EB743D75E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pages (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, excerpt LONGTEXT DEFAULT NULL, content LONGTEXT DEFAULT NULL, template_path VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, featured_image VARCHAR(255) DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description LONGTEXT DEFAULT NULL, tags JSON NOT NULL, published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', sort_order INT NOT NULL, INDEX IDX_2074E575F675F31B (author_id), UNIQUE INDEX UNIQ_2074E575989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site_config (id INT AUTO_INCREMENT NOT NULL, config_key VARCHAR(255) NOT NULL, config_value LONGTEXT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, active TINYINT(1) NOT NULL, status VARCHAR(20) NOT NULL, email_verified TINYINT(1) NOT NULL, email_verification_token VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Add foreign key constraints
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_BFDD3168F675F31B FOREIGN KEY (author_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7401B253C FOREIGN KEY (event_type_id) REFERENCES event_type (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7EE3A445A FOREIGN KEY (parent_event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event_condition ADD CONSTRAINT FK_3B43B67E71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E371F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E39D1C3019 FOREIGN KEY (participant_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE gallery ADD CONSTRAINT FK_472B783AF675F31B FOREIGN KEY (author_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F4E7AF8F FOREIGN KEY (gallery_id) REFERENCES gallery (id)');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045FA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE pages ADD CONSTRAINT FK_2074E575F675F31B FOREIGN KEY (author_id) REFERENCES users (id)');
        
        // Insert default diving levels
        $this->addSql("INSERT INTO diving_levels (code, name, description, sort_order, is_active, created_at) VALUES
            ('N1', 'N1 - Plongeur Encadré 20m', 'Premier niveau de plongée, plongées encadrées jusqu\'à 20m', 1, 1, NOW()),
            ('N2', 'N2 - Plongeur Autonome 20m', 'Plongée autonome jusqu\'à 20m, encadrée jusqu\'à 40m', 2, 1, NOW()),
            ('N3', 'N3 - Plongeur Autonome 60m', 'Plongée autonome jusqu\'à 60m', 3, 1, NOW()),
            ('N4', 'N4 - Guide de Palanquée', 'Guide de Palanquée, encadrement de plongeurs', 4, 1, NOW()),
            ('N5', 'N5 - Directeur de Plongée', 'Directeur de Plongée, responsabilité de site', 5, 1, NOW()),
            ('E1', 'E1 - Initiateur', 'Enseignement niveau 1', 6, 1, NOW()),
            ('E2', 'E2 - Moniteur Fédéral 1er', 'Enseignement jusqu\'au niveau 2', 7, 1, NOW()),
            ('E3', 'E3 - Moniteur Fédéral 2ème', 'Enseignement jusqu\'au niveau 4', 8, 1, NOW()),
            ('E4', 'E4 - Moniteur Fédéral 3ème', 'Enseignement tous niveaux', 9, 1, NOW()),
            ('RIFAP', 'RIFAP', 'Réactions et Intervention Face à un Accident de Plongée', 10, 1, NOW())");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE articles');
        $this->addSql('DROP TABLE attribute_definitions');
        $this->addSql('DROP TABLE diving_levels');
        $this->addSql('DROP TABLE entity_attributes');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_condition');
        $this->addSql('DROP TABLE event_participation');
        $this->addSql('DROP TABLE event_type');
        $this->addSql('DROP TABLE gallery');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE modules');
        $this->addSql('DROP TABLE pages');
        $this->addSql('DROP TABLE site_config');
        $this->addSql('DROP TABLE users');
    }
}
