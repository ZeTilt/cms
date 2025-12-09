<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209074715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute vérification CACI par DP (verified_at, verified_by) et supprime stockage fichier (RGPD)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD medical_certificate_verified_by_id INT DEFAULT NULL, ADD medical_certificate_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP medical_certificate_file');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9C062BC32 FOREIGN KEY (medical_certificate_verified_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1483A5E9C062BC32 ON users (medical_certificate_verified_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9C062BC32');
        $this->addSql('DROP INDEX IDX_1483A5E9C062BC32 ON users');
        $this->addSql('ALTER TABLE users ADD medical_certificate_file VARCHAR(255) DEFAULT NULL, DROP medical_certificate_verified_by_id, DROP medical_certificate_verified_at');
    }
}
