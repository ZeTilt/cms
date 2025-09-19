<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250919060820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add event meeting times, minimum diving level, and waiting list system';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD min_diving_level_id INT DEFAULT NULL, ADD club_meeting_time TIME DEFAULT NULL, ADD site_meeting_time TIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7ED70E60 FOREIGN KEY (min_diving_level_id) REFERENCES diving_levels (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7ED70E60 ON event (min_diving_level_id)');
        $this->addSql('ALTER TABLE event_participation ADD meeting_point VARCHAR(20) DEFAULT NULL, ADD is_waiting_list TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7ED70E60');
        $this->addSql('DROP INDEX IDX_3BAE0AA7ED70E60 ON event');
        $this->addSql('ALTER TABLE event DROP min_diving_level_id, DROP club_meeting_time, DROP site_meeting_time');
        $this->addSql('ALTER TABLE event_participation DROP meeting_point, DROP is_waiting_list');
    }
}
