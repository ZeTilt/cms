<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Performance optimization: Add database indexes for better query performance
 */
final class Version20250723140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance indexes for pages, articles, and modules tables';
    }

    public function up(Schema $schema): void
    {
        // Pages indexes
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pages_status ON pages(status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pages_type ON pages(type)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pages_published_at ON pages(published_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pages_status_published ON pages(status, published_at)');
        
        // Articles indexes
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_articles_status ON articles(status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_articles_published_at ON articles(published_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_articles_status_published ON articles(status, published_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_articles_category ON articles(category)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_articles_author ON articles(author_id)');
        
        // Modules indexes
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_modules_active ON modules(active)');
        
        // Users indexes (if not already present)
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_users_roles ON users(roles)');
    }

    public function down(Schema $schema): void
    {
        // Pages indexes
        $this->addSql('DROP INDEX IF EXISTS idx_pages_status');
        $this->addSql('DROP INDEX IF EXISTS idx_pages_type');
        $this->addSql('DROP INDEX IF EXISTS idx_pages_published_at');
        $this->addSql('DROP INDEX IF EXISTS idx_pages_status_published');
        
        // Articles indexes
        $this->addSql('DROP INDEX IF EXISTS idx_articles_status');
        $this->addSql('DROP INDEX IF EXISTS idx_articles_published_at');
        $this->addSql('DROP INDEX IF EXISTS idx_articles_status_published');
        $this->addSql('DROP INDEX IF EXISTS idx_articles_category');
        $this->addSql('DROP INDEX IF EXISTS idx_articles_author');
        
        // Modules indexes
        $this->addSql('DROP INDEX IF EXISTS idx_modules_active');
        
        // Users indexes
        $this->addSql('DROP INDEX IF EXISTS idx_users_roles');
    }
}