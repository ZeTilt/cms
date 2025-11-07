<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107110450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE boats (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, registration_number VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, capacity INT DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event ADD diving_director_id INT DEFAULT NULL, ADD boat_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7B9A14AE1 FOREIGN KEY (diving_director_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7A1E84A29 FOREIGN KEY (boat_id) REFERENCES boats (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7B9A14AE1 ON event (diving_director_id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7A1E84A29 ON event (boat_id)');
        $this->addSql('ALTER TABLE event_type ADD requires_diving_director TINYINT(1) NOT NULL, ADD requires_pilot TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7A1E84A29');
        $this->addSql('DROP TABLE boats');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7B9A14AE1');
        $this->addSql('DROP INDEX IDX_3BAE0AA7B9A14AE1 ON event');
        $this->addSql('DROP INDEX IDX_3BAE0AA7A1E84A29 ON event');
        $this->addSql('ALTER TABLE event DROP diving_director_id, DROP boat_id');
        $this->addSql('ALTER TABLE event_type DROP requires_diving_director, DROP requires_pilot');
    }
}
