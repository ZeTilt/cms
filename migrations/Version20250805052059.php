<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250805052059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_configuration (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, api_name VARCHAR(100) NOT NULL, api_key VARCHAR(255) DEFAULT NULL, partner_id VARCHAR(255) DEFAULT NULL, base_url VARCHAR(255) DEFAULT NULL, is_active BOOLEAN NOT NULL, additional_config CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8F105B7FD408F5 ON api_configuration (api_name)');
        $this->addSql('CREATE TABLE print_order (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_id INTEGER NOT NULL, order_number VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, shipping_address CLOB DEFAULT NULL --(DC2Type:json)
        , cewe_order_data CLOB DEFAULT NULL --(DC2Type:json)
        , cewe_order_id VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , shipped_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_844C19539395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_844C19539395C3F3 ON print_order (customer_id)');
        $this->addSql('CREATE TABLE print_order_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, print_order_id INTEGER NOT NULL, image_id INTEGER NOT NULL, print_format VARCHAR(50) NOT NULL, paper_type VARCHAR(20) NOT NULL, quantity INTEGER NOT NULL, unit_price NUMERIC(8, 2) NOT NULL, total_price NUMERIC(10, 2) NOT NULL, cewe_product_data CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_36F3D1E0ECD1F1FE FOREIGN KEY (print_order_id) REFERENCES print_order (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_36F3D1E03DA5256D FOREIGN KEY (image_id) REFERENCES image (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_36F3D1E0ECD1F1FE ON print_order_item (print_order_id)');
        $this->addSql('CREATE INDEX IDX_36F3D1E03DA5256D ON print_order_item (image_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE api_configuration');
        $this->addSql('DROP TABLE print_order');
        $this->addSql('DROP TABLE print_order_item');
    }
}
