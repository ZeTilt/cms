<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126100226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE content_blocks ADD page_id INT DEFAULT NULL, CHANGE article_id article_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE content_blocks ADD CONSTRAINT FK_A6DBE5D4C4663E4 FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A6DBE5D4C4663E4 ON content_blocks (page_id)');
        $this->addSql('ALTER TABLE pages ADD use_blocks TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE content_blocks DROP FOREIGN KEY FK_A6DBE5D4C4663E4');
        $this->addSql('DROP INDEX IDX_A6DBE5D4C4663E4 ON content_blocks');
        $this->addSql('ALTER TABLE content_blocks DROP page_id, CHANGE article_id article_id INT NOT NULL');
        $this->addSql('ALTER TABLE pages DROP use_blocks');
    }
}
