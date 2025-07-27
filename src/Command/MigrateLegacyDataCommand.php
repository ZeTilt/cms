<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\UserType;
use App\Entity\UserTypeAttribute;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-legacy-data',
    description: 'Migrate legacy user attributes to new UserPlus architecture',
)]
class MigrateLegacyDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run migration in dry-run mode (no changes)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force migration even if data exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');

        $io->title('ZeTilt CMS - Legacy Data Migration');

        if ($dryRun) {
            $io->note('Running in DRY-RUN mode - no changes will be made');
        }

        // Check if legacy tables exist
        $legacyTables = $this->checkLegacyTables();
        if (empty($legacyTables)) {
            $io->success('No legacy tables found - migration not needed');
            return Command::SUCCESS;
        }

        $io->section('Found legacy tables: ' . implode(', ', $legacyTables));

        // Check if new data already exists
        if (!$force && $this->hasNewData()) {
            $io->warning('New UserPlus data already exists. Use --force to override');
            return Command::FAILURE;
        }

        // Migrate user attributes
        if (in_array('user_attributes', $legacyTables)) {
            $this->migrateUserAttributes($io, $dryRun);
        }

        // Migrate entity attributes
        if (in_array('entity_attributes', $legacyTables)) {
            $this->migrateEntityAttributes($io, $dryRun);
        }

        $io->success('Legacy data migration completed successfully!');

        return Command::SUCCESS;
    }

    private function checkLegacyTables(): array
    {
        $tables = [];
        $schema = $this->connection->createSchemaManager();
        $existingTables = $schema->listTableNames();

        $legacyTableNames = ['user_attributes', 'entity_attributes'];
        
        foreach ($legacyTableNames as $tableName) {
            if (in_array($tableName, $existingTables)) {
                $tables[] = $tableName;
            }
        }

        return $tables;
    }

    private function hasNewData(): bool
    {
        $userTypeCount = $this->entityManager->getRepository(UserType::class)->count([]);
        $userTypeAttributeCount = $this->entityManager->getRepository(UserTypeAttribute::class)->count([]);
        
        return $userTypeCount > 0 || $userTypeAttributeCount > 0;
    }

    private function migrateUserAttributes(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Migrating user_attributes table');

        // Get legacy user attributes
        $sql = "SELECT DISTINCT attribute_key, attribute_type FROM user_attributes ORDER BY attribute_key";
        $legacyAttributes = $this->connection->fetchAllAssociative($sql);

        if (empty($legacyAttributes)) {
            $io->note('No user attributes found in legacy table');
            return;
        }

        $io->text(sprintf('Found %d unique attributes', count($legacyAttributes)));

        if (!$dryRun) {
            // Create default user type
            $defaultUserType = new UserType();
            $defaultUserType->setName('legacy_user');
            $defaultUserType->setDisplayName('Legacy User');
            $defaultUserType->setDescription('Migrated from legacy user_attributes');
            $defaultUserType->setActive(true);
            
            $this->entityManager->persist($defaultUserType);
            $this->entityManager->flush();

            $io->text('Created default user type: legacy_user');

            // Create user type attributes
            foreach ($legacyAttributes as $attr) {
                $typeAttribute = new UserTypeAttribute();
                $typeAttribute->setUserType($defaultUserType);
                $typeAttribute->setAttributeKey($attr['attribute_key']);
                $typeAttribute->setDisplayName(ucfirst(str_replace('_', ' ', $attr['attribute_key'])));
                $typeAttribute->setAttributeType($attr['attribute_type'] ?: 'text');
                $typeAttribute->setRequired(false);
                $typeAttribute->setActive(true);
                
                $this->entityManager->persist($typeAttribute);
            }

            $this->entityManager->flush();
            $io->text(sprintf('Created %d user type attributes', count($legacyAttributes)));

            // Assign users to the new type and migrate their attribute values
            $this->migrateUserAttributeValues($io, $defaultUserType);
        } else {
            $io->text('[DRY RUN] Would create default user type and migrate attributes');
            foreach ($legacyAttributes as $attr) {
                $io->text(sprintf('  - %s (%s)', $attr['attribute_key'], $attr['attribute_type']));
            }
        }
    }

    private function migrateUserAttributeValues(SymfonyStyle $io, UserType $userType): void
    {
        // Get all users without a user type
        $users = $this->entityManager->getRepository(User::class)->findBy(['userType' => null]);
        
        $io->text(sprintf('Assigning user type to %d users', count($users)));

        foreach ($users as $user) {
            $user->setUserType($userType);
            
            // Get legacy attributes for this user
            $sql = "SELECT attribute_key, attribute_value FROM user_attributes WHERE user_id = ?";
            $legacyUserAttrs = $this->connection->fetchAllAssociative($sql, [$user->getId()]);
            
            // Set attribute values
            foreach ($legacyUserAttrs as $attr) {
                $user->setUserAttributeValue(
                    $attr['attribute_key'], 
                    $attr['attribute_value'],
                    'text' // Default type
                );
            }
        }

        $this->entityManager->flush();
        $io->text('Migrated user attribute values');
    }

    private function migrateEntityAttributes(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Analyzing entity_attributes table');

        // Get entity attribute statistics
        $sql = "SELECT entity_type, COUNT(*) as count FROM entity_attributes GROUP BY entity_type";
        $stats = $this->connection->fetchAllAssociative($sql);

        if (empty($stats)) {
            $io->note('No entity attributes found in legacy table');
            return;
        }

        $io->table(['Entity Type', 'Count'], $stats);

        if (!$dryRun) {
            $io->warning('Entity attributes migration not yet implemented');
            $io->text('This data will be preserved for manual migration if needed');
        } else {
            $io->text('[DRY RUN] Would analyze entity attributes for migration');
        }
    }
}
