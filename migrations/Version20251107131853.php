<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107131853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE push_subscriptions (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, endpoint VARCHAR(500) NOT NULL, public_key VARCHAR(255) NOT NULL, auth_token VARCHAR(255) NOT NULL, user_agent VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', notify_event_registration TINYINT(1) NOT NULL, notify_event_cancellation TINYINT(1) NOT NULL, notify_waiting_list_promotion TINYINT(1) NOT NULL, notify_as_dp TINYINT(1) NOT NULL, INDEX IDX_3FEC449DA76ED395 (user_id), UNIQUE INDEX unique_subscription (user_id, endpoint), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE push_subscriptions ADD CONSTRAINT FK_3FEC449DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE push_subscriptions DROP FOREIGN KEY FK_3FEC449DA76ED395');
        $this->addSql('DROP TABLE push_subscriptions');
    }
}
