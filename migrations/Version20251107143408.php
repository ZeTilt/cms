<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107143408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification_history (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, event_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, url VARCHAR(500) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', opened_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', clicked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', group_tag VARCHAR(100) DEFAULT NULL, INDEX IDX_32A4FAFCA76ED395 (user_id), INDEX IDX_32A4FAFC71F7E88B (event_id), INDEX idx_user_created (user_id, created_at), INDEX idx_event_created (event_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification_history ADD CONSTRAINT FK_32A4FAFCA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification_history ADD CONSTRAINT FK_32A4FAFC71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification_history DROP FOREIGN KEY FK_32A4FAFCA76ED395');
        $this->addSql('ALTER TABLE notification_history DROP FOREIGN KEY FK_32A4FAFC71F7E88B');
        $this->addSql('DROP TABLE notification_history');
    }
}
