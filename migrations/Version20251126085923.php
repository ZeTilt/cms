<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126085923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add content blocks system for articles (block-based editor)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE content_blocks (id INT AUTO_INCREMENT NOT NULL, article_id INT NOT NULL, type VARCHAR(50) NOT NULL, data JSON NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_A6DBE5D47294869C (article_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE content_blocks ADD CONSTRAINT FK_A6DBE5D47294869C FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE articles ADD use_blocks TINYINT(1) DEFAULT 0 NOT NULL, ADD featured_image_alt VARCHAR(255) DEFAULT NULL, ADD featured_image_caption VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE content_blocks DROP FOREIGN KEY FK_A6DBE5D47294869C');
        $this->addSql('DROP TABLE content_blocks');
        $this->addSql('ALTER TABLE articles DROP use_blocks, DROP featured_image_alt, DROP featured_image_caption');
    }
}
