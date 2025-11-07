<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106115716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Simplification architecture: suppression galeries privées + ajout champs classiques User (remplacement EAV)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gallery DROP visibility, DROP access_code');
        $this->addSql('ALTER TABLE users ADD licence_number VARCHAR(50) DEFAULT NULL, ADD medical_certificate_date DATE DEFAULT NULL, ADD medical_certificate_expiry DATE DEFAULT NULL, ADD insurance_number VARCHAR(100) DEFAULT NULL, ADD insurance_expiry DATE DEFAULT NULL, ADD emergency_contact_name VARCHAR(255) DEFAULT NULL, ADD emergency_contact_phone VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gallery ADD visibility VARCHAR(20) DEFAULT \'public\' NOT NULL, ADD access_code VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE users DROP licence_number, DROP medical_certificate_date, DROP medical_certificate_expiry, DROP insurance_number, DROP insurance_expiry, DROP emergency_contact_name, DROP emergency_contact_phone');
    }
}
