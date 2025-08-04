<?php

namespace App\Command;

use App\Entity\AttributeDefinition;
use App\Service\EavService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-to-unified-eav',
    description: 'Migrate all attribute systems to unified EAV (entity_attributes)',
)]
class MigrateToUnifiedEavCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $connection,
        private EavService $eavService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run migration in dry-run mode (no changes)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force migration even if unified EAV data exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');

        $io->title('ZeTilt CMS - Unified EAV Migration');

        if ($dryRun) {
            $io->note('Running in DRY-RUN mode - no changes will be made');
        }

        // Check if unified EAV already has data
        if (!$force && $this->hasUnifiedEavData()) {
            $io->warning('Unified EAV (entity_attributes) already has data. Use --force to override');
            return Command::FAILURE;
        }

        // Step 1: Migrate UserAttribute data to unified EAV
        $this->migrateUserAttributes($io, $dryRun);

        // Step 2: Create AttributeDefinitions from UserTypeAttribute
        $this->migrateUserTypeAttributes($io, $dryRun);

        if (!$dryRun) {
            $io->success('Unified EAV migration completed successfully!');
        } else {
            $io->success('Dry-run completed - no changes were made');
        }

        return Command::SUCCESS;
    }

    private function hasUnifiedEavData(): bool
    {
        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM entity_attributes');
        return $count > 0;
    }

    private function migrateUserAttributes(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Migrating UserAttribute data to unified EAV');

        // Get all user attributes
        $userAttributes = $this->connection->fetchAllAssociative('
            SELECT ua.user_id, ua.attribute_key, ua.attribute_value, ua.attribute_type
            FROM user_attributes ua
            ORDER BY ua.user_id, ua.attribute_key
        ');

        if (empty($userAttributes)) {
            $io->note('No user attributes found to migrate');
            return;
        }

        $io->text(sprintf('Found %d user attributes to migrate', count($userAttributes)));

        if (!$dryRun) {
            $migratedCount = 0;
            foreach ($userAttributes as $attr) {
                $this->eavService->setAttribute(
                    'User',
                    $attr['user_id'],
                    $attr['attribute_key'],
                    $attr['attribute_value'],
                    $attr['attribute_type'] ?: 'text'
                );
                $migratedCount++;
            }
            
            $io->text(sprintf('Migrated %d user attributes to unified EAV', $migratedCount));
        } else {
            $io->text('[DRY RUN] Would migrate user attributes to entity_attributes table');
        }
    }

    private function migrateUserTypeAttributes(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('Creating AttributeDefinitions from UserTypeAttribute');

        // Get all user type attributes
        $userTypeAttributes = $this->connection->fetchAllAssociative('
            SELECT uta.attribute_key, uta.display_name, uta.attribute_type, 
                   uta.required, uta.default_value, uta.description, uta.display_order,
                   uta.validation_rules, uta.options
            FROM user_type_attributes uta
            INNER JOIN user_types ut ON uta.user_type_id = ut.id
            WHERE ut.active = 1 AND uta.active = 1
            ORDER BY uta.attribute_key
        ');

        if (empty($userTypeAttributes)) {
            $io->note('No user type attributes found to migrate');
            return;
        }

        $io->text(sprintf('Found %d user type attributes to migrate', count($userTypeAttributes)));

        if (!$dryRun) {
            $createdCount = 0;
            foreach ($userTypeAttributes as $attr) {
                // Check if AttributeDefinition already exists
                $existing = $this->entityManager->getRepository(AttributeDefinition::class)
                    ->findOneBy([
                        'entityType' => 'User',
                        'attributeName' => $attr['attribute_key']
                    ]);

                if ($existing) {
                    $io->text(sprintf('  - Skipping %s (already exists)', $attr['attribute_key']));
                    continue;
                }

                $definition = new AttributeDefinition();
                $definition->setEntityType('User')
                    ->setAttributeName($attr['attribute_key'])
                    ->setDisplayName($attr['display_name'])
                    ->setAttributeType($attr['attribute_type'] ?: 'text')
                    ->setRequired($attr['required'] ?: false)
                    ->setDefaultValue($attr['default_value'])
                    ->setDescription($attr['description'])
                    ->setDisplayOrder($attr['display_order'] ?: 0)
                    ->setValidationRules($attr['validation_rules'] ? json_decode($attr['validation_rules'], true) : null)
                    ->setOptions($attr['options'] ? json_decode($attr['options'], true) : null);

                $this->entityManager->persist($definition);
                $createdCount++;
                
                $io->text(sprintf('  + Created AttributeDefinition: %s', $attr['attribute_key']));
            }
            
            $this->entityManager->flush();
            $io->text(sprintf('Created %d AttributeDefinitions', $createdCount));
        } else {
            $io->text('[DRY RUN] Would create AttributeDefinitions for:');
            foreach ($userTypeAttributes as $attr) {
                $io->text(sprintf('  - %s (%s)', $attr['attribute_key'], $attr['attribute_type']));
            }
        }
    }
}