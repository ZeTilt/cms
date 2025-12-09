<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209084109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute gestion des cotisations (saison, montant, mode paiement, validation)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD membership_validated_by_id INT DEFAULT NULL, ADD membership_season VARCHAR(20) DEFAULT NULL, ADD membership_paid_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD membership_amount NUMERIC(10, 2) DEFAULT NULL, ADD membership_payment_method VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9CA7D8568 FOREIGN KEY (membership_validated_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1483A5E9CA7D8568 ON users (membership_validated_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9CA7D8568');
        $this->addSql('DROP INDEX IDX_1483A5E9CA7D8568 ON users');
        $this->addSql('ALTER TABLE users DROP membership_validated_by_id, DROP membership_season, DROP membership_paid_at, DROP membership_amount, DROP membership_payment_method');
    }
}
