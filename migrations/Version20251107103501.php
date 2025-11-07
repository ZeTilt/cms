<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107103501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE freediving_levels (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(20) NOT NULL, description LONGTEXT DEFAULT NULL, sort_order INT NOT NULL, is_active TINYINT(1) NOT NULL, is_instructor TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_C91163C677153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE users ADD highest_freediving_level_id INT DEFAULT NULL, ADD is_diver TINYINT(1) NOT NULL, ADD is_freediver TINYINT(1) NOT NULL, ADD is_pilot TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9306BA1C5 FOREIGN KEY (highest_freediving_level_id) REFERENCES freediving_levels (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E9306BA1C5 ON users (highest_freediving_level_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9306BA1C5');
        $this->addSql('DROP TABLE freediving_levels');
        $this->addSql('DROP INDEX IDX_1483A5E9306BA1C5 ON users');
        $this->addSql('ALTER TABLE users DROP highest_freediving_level_id, DROP is_diver, DROP is_freediver, DROP is_pilot');
    }
}
