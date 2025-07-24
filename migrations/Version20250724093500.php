<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for UserType and UserTypeAttribute entities
 */
final class Version20250724093500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add UserType and UserTypeAttribute tables and modify User table';
    }

    public function up(Schema $schema): void
    {
        // Create user_types table
        $this->addSql('CREATE TABLE user_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            name VARCHAR(50) NOT NULL, 
            display_name VARCHAR(100) NOT NULL, 
            description CLOB DEFAULT NULL, 
            active BOOLEAN NOT NULL DEFAULT 1, 
            config CLOB NOT NULL DEFAULT \'{}\'
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8EBE95635E237E06 ON user_types (name)');

        // Create user_type_attributes table
        $this->addSql('CREATE TABLE user_type_attributes (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            user_type_id INTEGER NOT NULL, 
            attribute_key VARCHAR(100) NOT NULL, 
            display_name VARCHAR(150) NOT NULL, 
            attribute_type VARCHAR(50) NOT NULL DEFAULT \'text\', 
            required BOOLEAN NOT NULL DEFAULT 0, 
            default_value CLOB DEFAULT NULL, 
            description CLOB DEFAULT NULL, 
            validation_rules CLOB DEFAULT NULL, 
            options CLOB DEFAULT NULL, 
            display_order INTEGER NOT NULL DEFAULT 0, 
            active BOOLEAN NOT NULL DEFAULT 1,
            CONSTRAINT FK_B2B5C8A69D419299 FOREIGN KEY (user_type_id) REFERENCES user_types (id)
        )');
        $this->addSql('CREATE INDEX IDX_B2B5C8A69D419299 ON user_type_attributes (user_type_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_type_attribute ON user_type_attributes (user_type_id, attribute_key)');

        // Add user_type_id to users table (SQLite compatible way)
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, roles, password, first_name, last_name, active, created_at, updated_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            user_type_id INTEGER DEFAULT NULL, 
            email VARCHAR(180) NOT NULL, 
            roles CLOB NOT NULL, 
            password VARCHAR(255) NOT NULL, 
            first_name VARCHAR(100) NOT NULL, 
            last_name VARCHAR(100) NOT NULL, 
            active BOOLEAN NOT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME DEFAULT NULL,
            CONSTRAINT FK_1483A5E99D419299 FOREIGN KEY (user_type_id) REFERENCES user_types (id)
        )');
        $this->addSql('INSERT INTO users (id, email, roles, password, first_name, last_name, active, created_at, updated_at) SELECT id, email, roles, password, first_name, last_name, active, created_at, updated_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E99D419299 ON users (user_type_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove user_type_id from users table
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, roles, password, first_name, last_name, active, created_at, updated_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            email VARCHAR(180) NOT NULL, 
            roles CLOB NOT NULL, 
            password VARCHAR(255) NOT NULL, 
            first_name VARCHAR(100) NOT NULL, 
            last_name VARCHAR(100) NOT NULL, 
            active BOOLEAN NOT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME DEFAULT NULL
        )');
        $this->addSql('INSERT INTO users (id, email, roles, password, first_name, last_name, active, created_at, updated_at) SELECT id, email, roles, password, first_name, last_name, active, created_at, updated_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');

        // Drop new tables
        $this->addSql('DROP TABLE user_type_attributes');
        $this->addSql('DROP TABLE user_types');
    }
}