<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour supprimer les colonnes JSON après migration vers EAV
 * ATTENTION : Exécutez d'abord la commande de migration des données !
 * php bin/console zetilt:migrate:json-to-eav --all
 */
final class Version20250730DropJsonColumns extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime les colonnes JSON metadata et tags après migration vers EAV';
    }

    public function up(Schema $schema): void
    {
        // ATTENTION : Cette migration est destructive !
        // Assurez-vous d'avoir migré les données avec :
        // php bin/console zetilt:migrate:json-to-eav --all
        
        // Supprimer la colonne metadata de la table users
        $this->addSql('ALTER TABLE users DROP COLUMN metadata');
        
        // Supprimer la colonne tags de la table articles
        $this->addSql('ALTER TABLE articles DROP COLUMN tags');
        
        // Optionnel : supprimer aussi meta_data si elle n'est pas utilisée
        // $this->addSql('ALTER TABLE articles DROP COLUMN meta_data');
    }

    public function down(Schema $schema): void
    {
        // Recréer les colonnes (les données seront perdues)
        $this->addSql('ALTER TABLE users ADD metadata JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE articles ADD tags JSON DEFAULT NULL');
        // $this->addSql('ALTER TABLE articles ADD meta_data JSON DEFAULT NULL');
    }
}