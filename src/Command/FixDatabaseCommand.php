<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-database',
    description: 'Fix database issues for MySQL compatibility',
)]
class FixDatabaseCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔧 Correction Base de Données MySQL');

        try {
            // 1. Vérifier et créer table entity_attributes
            $io->section('1. Table entity_attributes');
            if (!$this->tableExists('entity_attributes')) {
                $this->createEntityAttributesTable();
                $io->success('Table entity_attributes créée');
            } else {
                $io->info('Table entity_attributes existe déjà');
            }

            // 2. Vérifier colonnes users
            $io->section('2. Colonnes table users');
            $this->addUserColumns($io);

            // 3. Créer table diving_levels
            $io->section('3. Table diving_levels');
            if (!$this->tableExists('diving_levels')) {
                $this->createDivingLevelsTable();
                $io->success('Table diving_levels créée avec les niveaux de base');
            } else {
                $io->info('Table diving_levels existe déjà');
            }

            // 4. Ajouter colonnes event pour conditions
            $io->section('4. Colonnes conditions table event');
            $this->addEventConditionsColumns($io);

            // 5. Créer table event_participation
            $io->section('5. Table event_participation');
            if (!$this->tableExists('event_participation')) {
                $this->createEventParticipationTable();
                $io->success('Table event_participation créée');
            } else {
                $io->info('Table event_participation existe déjà');
            }

            $io->success('✅ Correction base de données terminée !');
            $io->note('Vous pouvez maintenant utiliser: php bin/console app:create-admin-user');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function tableExists(string $tableName): bool
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        
        if ($platform === 'sqlite') {
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='$tableName'";
        } else {
            $sql = "SHOW TABLES LIKE '$tableName'";
        }
        
        $result = $this->connection->executeQuery($sql);
        return $result->rowCount() > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        
        if ($platform === 'sqlite') {
            $sql = "PRAGMA table_info($tableName)";
            $result = $this->connection->executeQuery($sql);
            $columns = $result->fetchAllAssociative();
            return in_array($columnName, array_column($columns, 'name'));
        } else {
            $sql = "SHOW COLUMNS FROM $tableName LIKE '$columnName'";
            $result = $this->connection->executeQuery($sql);
            return $result->rowCount() > 0;
        }
    }

    private function isMySQL(): bool
    {
        return $this->connection->getDatabasePlatform()->getName() === 'mysql';
    }

    private function isSQLite(): bool
    {
        return $this->connection->getDatabasePlatform()->getName() === 'sqlite';
    }

    private function createEntityAttributesTable(): void
    {
        $sql = "CREATE TABLE entity_attributes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT NOT NULL,
            attribute_name VARCHAR(100) NOT NULL,
            attribute_value TEXT DEFAULT NULL,
            attribute_type VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX idx_entity_lookup (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->connection->executeStatement($sql);
    }

    private function addUserColumns(SymfonyStyle $io): void
    {
        $userColumns = [
            'status' => "ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'approved'",
            'email_verified' => "ALTER TABLE users ADD COLUMN email_verified BOOLEAN NOT NULL DEFAULT 1",
            'email_verification_token' => "ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(100) DEFAULT NULL"
        ];

        foreach ($userColumns as $column => $sql) {
            if (!$this->columnExists('users', $column)) {
                $this->connection->executeStatement($sql);
                $io->writeln("  ✅ Colonne $column ajoutée");
            } else {
                $io->writeln("  ✅ Colonne $column existe");
            }
        }
    }

    private function createDivingLevelsTable(): void
    {
        $sql = "CREATE TABLE diving_levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20) NOT NULL UNIQUE,
            description TEXT DEFAULT NULL,
            sort_order INT DEFAULT 0,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->connection->executeStatement($sql);

        // Insérer les niveaux de base
        $levels = [
            ['N1', 'N1 - Plongeur Encadré 20m', 'Niveau 1 FFESSM', 10],
            ['N2', 'N2 - Plongeur Autonome 20m / Encadré 40m', 'Niveau 2 FFESSM', 20],
            ['N3', 'N3 - Plongeur Autonome 60m', 'Niveau 3 FFESSM', 30],
            ['N4', 'N4 - Guide de Palanquée', 'Niveau 4 FFESSM', 40],
            ['N5', 'N5 - Directeur de Plongée', 'Niveau 5 FFESSM', 50],
            ['E1', 'E1 - Initiateur', 'Encadrement niveau 1', 60],
            ['E2', 'E2 - Moniteur Fédéral 1er Degré', 'Encadrement niveau 2', 70],
            ['E3', 'E3 - Moniteur Fédéral 2ème Degré', 'Encadrement niveau 3', 80],
            ['E4', 'E4 - Moniteur Fédéral 3ème Degré', 'Encadrement niveau 4', 90],
            ['RIFAP', 'RIFAP', 'Réactions et Intervention Face à un Accident de Plongée', 100]
        ];

        foreach ($levels as $level) {
            $this->connection->insert('diving_levels', [
                'code' => $level[0],
                'name' => $level[1],
                'description' => $level[2],
                'sort_order' => $level[3],
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    private function addEventConditionsColumns(SymfonyStyle $io): void
    {
        if (!$this->tableExists('event')) {
            $io->warning('Table event non trouvée, ignorée');
            return;
        }

        $eventColumns = [
            'min_diving_level' => "ALTER TABLE event ADD COLUMN min_diving_level VARCHAR(50) DEFAULT NULL",
            'min_age' => "ALTER TABLE event ADD COLUMN min_age INT DEFAULT NULL",
            'max_age' => "ALTER TABLE event ADD COLUMN max_age INT DEFAULT NULL",
            'requires_medical_certificate' => "ALTER TABLE event ADD COLUMN requires_medical_certificate BOOLEAN NOT NULL DEFAULT 0",
            'medical_certificate_validity_days' => "ALTER TABLE event ADD COLUMN medical_certificate_validity_days INT DEFAULT NULL",
            'requires_swimming_test' => "ALTER TABLE event ADD COLUMN requires_swimming_test BOOLEAN NOT NULL DEFAULT 0",
            'additional_requirements' => "ALTER TABLE event ADD COLUMN additional_requirements TEXT DEFAULT NULL"
        ];

        foreach ($eventColumns as $column => $sql) {
            if (!$this->columnExists('event', $column)) {
                $this->connection->executeStatement($sql);
                $io->writeln("  ✅ Colonne $column ajoutée");
            } else {
                $io->writeln("  ✅ Colonne $column existe");
            }
        }
    }

    private function createEventParticipationTable(): void
    {
        $sql = "CREATE TABLE event_participation (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            participant_id INT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'registered',
            registration_date DATETIME NOT NULL,
            confirmation_date DATETIME DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            FOREIGN KEY (event_id) REFERENCES event(id) ON DELETE CASCADE,
            FOREIGN KEY (participant_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_participation (event_id, participant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->connection->executeStatement($sql);
    }
}