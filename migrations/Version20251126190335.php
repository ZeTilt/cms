<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126190335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des tables menu et menu_item pour la gestion des menus de navigation';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE menu (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, location VARCHAR(50) NOT NULL, position INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_7D053A935E9E89CB (location), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE menu_item (id INT AUTO_INCREMENT NOT NULL, menu_id INT NOT NULL, parent_id INT DEFAULT NULL, page_id INT DEFAULT NULL, label VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, route VARCHAR(100) DEFAULT NULL, route_params JSON DEFAULT NULL, custom_url VARCHAR(255) DEFAULT NULL, icon VARCHAR(50) DEFAULT NULL, position INT NOT NULL, active TINYINT(1) NOT NULL, roles JSON DEFAULT NULL, css_class VARCHAR(100) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, open_in_new_tab TINYINT(1) NOT NULL, INDEX IDX_D754D550CCD7E912 (menu_id), INDEX IDX_D754D550727ACA70 (parent_id), INDEX IDX_D754D550C4663E4 (page_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE menu_item ADD CONSTRAINT FK_D754D550CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_item ADD CONSTRAINT FK_D754D550727ACA70 FOREIGN KEY (parent_id) REFERENCES menu_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_item ADD CONSTRAINT FK_D754D550C4663E4 FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu_item DROP FOREIGN KEY FK_D754D550CCD7E912');
        $this->addSql('ALTER TABLE menu_item DROP FOREIGN KEY FK_D754D550727ACA70');
        $this->addSql('ALTER TABLE menu_item DROP FOREIGN KEY FK_D754D550C4663E4');
        $this->addSql('DROP TABLE menu');
        $this->addSql('DROP TABLE menu_item');
    }
}
