<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915081632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE diving_levels (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(20) NOT NULL, description LONGTEXT DEFAULT NULL, sort_order INT NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_FA5AB8A977153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Insert default diving levels
        $this->addSql("INSERT INTO diving_levels (code, name, description, sort_order, is_active, created_at) VALUES
            ('N1', 'N1 - Plongeur Encadré 20m', 'Premier niveau de plongée, plongées encadrées jusqu\'à 20m', 1, 1, NOW()),
            ('N2', 'N2 - Plongeur Autonome 20m', 'Plongée autonome jusqu\'à 20m, encadrée jusqu\'à 40m', 2, 1, NOW()),
            ('N3', 'N3 - Plongeur Autonome 60m', 'Plongée autonome jusqu\'à 60m', 3, 1, NOW()),
            ('N4', 'N4 - Guide de Palanquée', 'Guide de Palanquée, encadrement de plongeurs', 4, 1, NOW()),
            ('N5', 'N5 - Directeur de Plongée', 'Directeur de Plongée, responsabilité de site', 5, 1, NOW()),
            ('E1', 'E1 - Initiateur', 'Enseignement niveau 1', 6, 1, NOW()),
            ('E2', 'E2 - Moniteur Fédéral 1er', 'Enseignement jusqu\'au niveau 2', 7, 1, NOW()),
            ('E3', 'E3 - Moniteur Fédéral 2ème', 'Enseignement jusqu\'au niveau 4', 8, 1, NOW()),
            ('E4', 'E4 - Moniteur Fédéral 3ème', 'Enseignement tous niveaux', 9, 1, NOW()),
            ('RIFAP', 'RIFAP', 'Réactions et Intervention Face à un Accident de Plongée', 10, 1, NOW())");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE diving_levels');
    }
}
