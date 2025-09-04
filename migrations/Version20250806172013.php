<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250806172013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE prodigi_product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sku VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, category VARCHAR(50) NOT NULL, base_price NUMERIC(10, 2) NOT NULL, paper_type VARCHAR(100) NOT NULL, dimensions CLOB DEFAULT NULL --(DC2Type:json)
        , attributes CLOB DEFAULT NULL --(DC2Type:json)
        , api_data CLOB DEFAULT NULL --(DC2Type:json)
        , is_available BOOLEAN NOT NULL, last_updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F21B4C4DF9038C4 ON prodigi_product (sku)');
        $this->addSql('CREATE INDEX idx_sku ON prodigi_product (sku)');
        $this->addSql('CREATE INDEX idx_category ON prodigi_product (category)');
        $this->addSql('CREATE INDEX idx_last_updated ON prodigi_product (last_updated_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE prodigi_product');
    }
}
