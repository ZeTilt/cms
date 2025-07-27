<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250725074831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bookings (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, service_id INTEGER DEFAULT NULL, event_id INTEGER DEFAULT NULL, status VARCHAR(20) NOT NULL, notes CLOB DEFAULT NULL, booking_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , start_time DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , end_time DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , participants INTEGER DEFAULT NULL, total_price NUMERIC(10, 2) DEFAULT NULL, payment_status VARCHAR(20) DEFAULT NULL, customer_info CLOB DEFAULT NULL --(DC2Type:json)
        , metadata CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_7A853C35A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7A853C35ED5CA9E6 FOREIGN KEY (service_id) REFERENCES services (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7A853C3571F7E88B FOREIGN KEY (event_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7A853C35A76ED395 ON bookings (user_id)');
        $this->addSql('CREATE INDEX IDX_7A853C35ED5CA9E6 ON bookings (service_id)');
        $this->addSql('CREATE INDEX IDX_7A853C3571F7E88B ON bookings (event_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE bookings');
    }
}
