<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ZeTilt CMS - Migration consolidée complète
 * 
 * Cette migration crée la structure complète de la base de données ZeTilt CMS
 * avec toutes les entités, relations et données initiales nécessaires.
 * 
 * Fonctionnalités incluses:
 * - Système d'authentification et de rôles hiérarchiques
 * - Gestion des utilisateurs avec types et attributs dynamiques (EAV)
 * - Système d'événements et d'inscriptions (plongées, formations)
 * - Gestion des articles et pages
 * - Galeries d'images avec accès privé
 * - Services et réservations
 * - Certifications de plongée
 * - Système de modules
 * - Support multilingue
 * 
 * Version: 1.0.0
 * Date: 2025-01-30
 * Target: Club de plongée et photographe professionnel
 */
final class Version20250730052909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ZeTilt CMS - Structure complète avec rôles hiérarchiques et système EAV';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE articles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content CLOB NOT NULL, excerpt CLOB DEFAULT NULL, featured_image VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, published_at DATETIME DEFAULT NULL, meta_data CLOB DEFAULT NULL --(DC2Type:json)
        , category VARCHAR(255) DEFAULT NULL, tags CLOB DEFAULT NULL --(DC2Type:json)
        , CONSTRAINT FK_BFDD3168F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BFDD3168989D9B62 ON articles (slug)');
        $this->addSql('CREATE INDEX IDX_BFDD3168F675F31B ON articles (author_id)');
        $this->addSql('CREATE TABLE attribute_definitions (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, entity_type VARCHAR(50) NOT NULL, attribute_name VARCHAR(100) NOT NULL, display_name VARCHAR(100) NOT NULL, attribute_type VARCHAR(20) NOT NULL, required BOOLEAN NOT NULL, default_value CLOB DEFAULT NULL, description CLOB DEFAULT NULL, options CLOB DEFAULT NULL --(DC2Type:json)
        , validation_rules CLOB DEFAULT NULL --(DC2Type:json)
        , display_order INTEGER NOT NULL, active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX idx_attribute_entity_lookup ON attribute_definitions (entity_type)');
        $this->addSql('CREATE TABLE bookings (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, service_id INTEGER DEFAULT NULL, event_id INTEGER DEFAULT NULL, status VARCHAR(20) NOT NULL, notes CLOB DEFAULT NULL, booking_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , start_time DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , end_time DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , participants INTEGER DEFAULT NULL, total_price NUMERIC(10, 2) DEFAULT NULL, payment_status VARCHAR(20) DEFAULT NULL, customer_info CLOB DEFAULT NULL --(DC2Type:json)
        , metadata CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_7A853C35A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7A853C35ED5CA9E6 FOREIGN KEY (service_id) REFERENCES services (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7A853C3571F7E88B FOREIGN KEY (event_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7A853C35A76ED395 ON bookings (user_id)');
        $this->addSql('CREATE INDEX IDX_7A853C35ED5CA9E6 ON bookings (service_id)');
        $this->addSql('CREATE INDEX IDX_7A853C3571F7E88B ON bookings (event_id)');
        $this->addSql('CREATE TABLE business_contacts (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, assigned_to_id INTEGER NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, company VARCHAR(255) DEFAULT NULL, position VARCHAR(100) DEFAULT NULL, status VARCHAR(50) NOT NULL, source VARCHAR(50) NOT NULL, notes CLOB DEFAULT NULL, tags CLOB NOT NULL --(DC2Type:json)
        , address CLOB DEFAULT NULL --(DC2Type:json)
        , custom_fields CLOB DEFAULT NULL --(DC2Type:json)
        , last_contact_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , next_follow_up_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_3B701BE5F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3B701BE5E7927C74 ON business_contacts (email)');
        $this->addSql('CREATE INDEX IDX_3B701BE5F4BD7827 ON business_contacts (assigned_to_id)');
        $this->addSql('CREATE TABLE certifications (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, level VARCHAR(50) NOT NULL, issuing_organization VARCHAR(100) DEFAULT NULL, certification_type VARCHAR(50) NOT NULL, validity_duration_months INTEGER DEFAULT NULL, prerequisites CLOB DEFAULT NULL --(DC2Type:json)
        , competencies CLOB DEFAULT NULL --(DC2Type:json)
        , color VARCHAR(255) DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, is_active BOOLEAN NOT NULL, requires_renewal BOOLEAN NOT NULL, allow_self_certification BOOLEAN NOT NULL, display_order INTEGER DEFAULT NULL, metadata CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE TABLE entity_attributes (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INTEGER NOT NULL, attribute_name VARCHAR(100) NOT NULL, attribute_value CLOB DEFAULT NULL, attribute_type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX idx_entity_lookup ON entity_attributes (entity_type, entity_id)');
        $this->addSql('CREATE TABLE event_attributes (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_id INTEGER NOT NULL, attribute_key VARCHAR(100) NOT NULL, attribute_type VARCHAR(20) NOT NULL, attribute_value CLOB DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_6975475F71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6975475F71F7E88B ON event_attributes (event_id)');
        $this->addSql('CREATE TABLE event_registrations (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_id INTEGER NOT NULL, user_id INTEGER NOT NULL, status VARCHAR(20) NOT NULL, notes CLOB DEFAULT NULL, metadata CLOB DEFAULT NULL --(DC2Type:json)
        , registered_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , number_of_spots INTEGER DEFAULT 1 NOT NULL, departure_location VARCHAR(20) DEFAULT \'club\' NOT NULL, registration_comment CLOB DEFAULT NULL, CONSTRAINT FK_7787E14B71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7787E14BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7787E14B71F7E88B ON event_registrations (event_id)');
        $this->addSql('CREATE INDEX IDX_7787E14BA76ED395 ON event_registrations (user_id)');
        $this->addSql('CREATE TABLE events (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, organizer_id INTEGER NOT NULL, pilot_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, short_description CLOB DEFAULT NULL, start_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , end_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , location VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, type VARCHAR(50) NOT NULL, tags CLOB NOT NULL --(DC2Type:json)
        , featured_image VARCHAR(255) DEFAULT NULL, meta_data CLOB DEFAULT NULL --(DC2Type:json)
        , max_participants INTEGER DEFAULT NULL, current_participants INTEGER NOT NULL, requires_registration BOOLEAN NOT NULL, is_recurring BOOLEAN NOT NULL, recurring_config CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , club_departure_time DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , dock_departure_time DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , diving_comments CLOB DEFAULT NULL, CONSTRAINT FK_5387574A876C4DDA FOREIGN KEY (organizer_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5387574ACE55439B FOREIGN KEY (pilot_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5387574A989D9B62 ON events (slug)');
        $this->addSql('CREATE INDEX IDX_5387574A876C4DDA ON events (organizer_id)');
        $this->addSql('CREATE INDEX IDX_5387574ACE55439B ON events (pilot_id)');
        $this->addSql('CREATE TABLE gallery (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, visibility VARCHAR(20) DEFAULT \'public\' NOT NULL, access_code VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , metadata CLOB DEFAULT NULL --(DC2Type:json)
        , CONSTRAINT FK_472B783AF675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_472B783A989D9B62 ON gallery (slug)');
        $this->addSql('CREATE INDEX IDX_472B783AF675F31B ON gallery (author_id)');
        $this->addSql('CREATE TABLE image (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, gallery_id INTEGER NOT NULL, uploaded_by_id INTEGER NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INTEGER NOT NULL, width INTEGER DEFAULT NULL, height INTEGER DEFAULT NULL, alt VARCHAR(255) DEFAULT NULL, caption CLOB DEFAULT NULL, position INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , exif_data CLOB DEFAULT NULL --(DC2Type:json)
        , CONSTRAINT FK_C53D045F4E7AF8F FOREIGN KEY (gallery_id) REFERENCES gallery (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C53D045FA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C53D045F4E7AF8F ON image (gallery_id)');
        $this->addSql('CREATE INDEX IDX_C53D045FA2B28FE8 ON image (uploaded_by_id)');
        $this->addSql('CREATE TABLE modules (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, display_name VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, active BOOLEAN NOT NULL, config CLOB NOT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2EB743D75E237E06 ON modules (name)');
        $this->addSql('CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, excerpt CLOB DEFAULT NULL, template_path VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, featured_image VARCHAR(255) DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description CLOB DEFAULT NULL, tags CLOB NOT NULL --(DC2Type:json)
        , published_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , sort_order INTEGER NOT NULL, CONSTRAINT FK_2074E575F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2074E575989D9B62 ON pages (slug)');
        $this->addSql('CREATE INDEX IDX_2074E575F675F31B ON pages (author_id)');
        $this->addSql('CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, display_name VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, hierarchy INTEGER NOT NULL, active BOOLEAN NOT NULL, permissions CLOB NOT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B63E2EC75E237E06 ON roles (name)');
        $this->addSql('CREATE TABLE services (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, short_description CLOB DEFAULT NULL, price NUMERIC(10, 2) DEFAULT NULL, currency VARCHAR(10) DEFAULT NULL, pricing_type VARCHAR(50) DEFAULT NULL, duration INTEGER DEFAULT NULL, status VARCHAR(20) NOT NULL, category VARCHAR(100) DEFAULT NULL, features CLOB NOT NULL --(DC2Type:json)
        , featured_image VARCHAR(255) DEFAULT NULL, gallery CLOB DEFAULT NULL --(DC2Type:json)
        , metadata CLOB DEFAULT NULL --(DC2Type:json)
        , bookable BOOLEAN NOT NULL, featured BOOLEAN NOT NULL, display_order INTEGER DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7332E169989D9B62 ON services (slug)');
        $this->addSql('CREATE TABLE system_settings (setting_key VARCHAR(100) NOT NULL, setting_value CLOB DEFAULT NULL, setting_type VARCHAR(50) DEFAULT NULL, description CLOB DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(setting_key))');
        $this->addSql('CREATE TABLE testimonials (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, related_service_id INTEGER DEFAULT NULL, related_event_id INTEGER DEFAULT NULL, submitted_by_id INTEGER DEFAULT NULL, client_name VARCHAR(255) NOT NULL, client_title VARCHAR(255) DEFAULT NULL, client_company VARCHAR(255) DEFAULT NULL, client_email VARCHAR(255) DEFAULT NULL, content CLOB NOT NULL, short_content CLOB DEFAULT NULL, rating INTEGER DEFAULT NULL, status VARCHAR(20) NOT NULL, category VARCHAR(50) DEFAULT NULL, project_name VARCHAR(255) DEFAULT NULL, project_date DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , client_photo VARCHAR(255) DEFAULT NULL, project_image VARCHAR(255) DEFAULT NULL, tags CLOB DEFAULT NULL --(DC2Type:json)
        , featured BOOLEAN NOT NULL, allow_public_display BOOLEAN NOT NULL, display_order INTEGER DEFAULT NULL, metadata CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , approved_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_383115796791A156 FOREIGN KEY (related_service_id) REFERENCES services (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_38311579D774A626 FOREIGN KEY (related_event_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3831157979F7D87D FOREIGN KEY (submitted_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_383115796791A156 ON testimonials (related_service_id)');
        $this->addSql('CREATE INDEX IDX_38311579D774A626 ON testimonials (related_event_id)');
        $this->addSql('CREATE INDEX IDX_3831157979F7D87D ON testimonials (submitted_by_id)');
        $this->addSql('CREATE TABLE translations (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INTEGER NOT NULL, field_name VARCHAR(100) NOT NULL, locale VARCHAR(5) NOT NULL, value CLOB NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX unique_translation ON translations (entity_type, entity_id, field_name, locale)');
        $this->addSql('CREATE TABLE user_attributes (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, attribute_key VARCHAR(100) NOT NULL, attribute_value CLOB DEFAULT NULL, attribute_type VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_96FC589CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_96FC589CA76ED395 ON user_attributes (user_id)');
        $this->addSql('CREATE TABLE user_certifications (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, certification_id INTEGER NOT NULL, obtained_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , expires_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , status VARCHAR(50) NOT NULL, certificate_number VARCHAR(100) DEFAULT NULL, issuing_authority VARCHAR(255) DEFAULT NULL, notes CLOB DEFAULT NULL, metadata CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5088A98CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5088A98CCB47068A FOREIGN KEY (certification_id) REFERENCES certifications (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5088A98CA76ED395 ON user_certifications (user_id)');
        $this->addSql('CREATE INDEX IDX_5088A98CCB47068A ON user_certifications (certification_id)');
        $this->addSql('CREATE TABLE user_roles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, role_id INTEGER NOT NULL, assigned_by_id INTEGER DEFAULT NULL, assigned_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , active BOOLEAN NOT NULL, CONSTRAINT FK_54FCD59FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_54FCD59FD60322AC FOREIGN KEY (role_id) REFERENCES roles (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_54FCD59F6E6F1246 FOREIGN KEY (assigned_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_54FCD59FA76ED395 ON user_roles (user_id)');
        $this->addSql('CREATE INDEX IDX_54FCD59FD60322AC ON user_roles (role_id)');
        $this->addSql('CREATE INDEX IDX_54FCD59F6E6F1246 ON user_roles (assigned_by_id)');
        $this->addSql('CREATE UNIQUE INDEX user_role_unique ON user_roles (user_id, role_id)');
        $this->addSql('CREATE TABLE user_type_attributes (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_type_id INTEGER NOT NULL, attribute_key VARCHAR(100) NOT NULL, display_name VARCHAR(150) NOT NULL, attribute_type VARCHAR(50) NOT NULL, required BOOLEAN NOT NULL, default_value CLOB DEFAULT NULL, description CLOB DEFAULT NULL, validation_rules CLOB DEFAULT NULL --(DC2Type:json)
        , options CLOB DEFAULT NULL --(DC2Type:json)
        , display_order INTEGER NOT NULL, active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_3E4424DA9D419299 FOREIGN KEY (user_type_id) REFERENCES user_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_3E4424DA9D419299 ON user_type_attributes (user_type_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_type_attribute ON user_type_attributes (user_type_id, attribute_key)');
        $this->addSql('CREATE TABLE user_types (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, display_name VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, active BOOLEAN NOT NULL, config CLOB NOT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BBF272685E237E06 ON user_types (name)');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_type_id INTEGER DEFAULT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , metadata CLOB DEFAULT NULL --(DC2Type:json)
        , CONSTRAINT FK_1483A5E99D419299 FOREIGN KEY (user_type_id) REFERENCES user_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E99D419299 ON users (user_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE articles');
        $this->addSql('DROP TABLE attribute_definitions');
        $this->addSql('DROP TABLE bookings');
        $this->addSql('DROP TABLE business_contacts');
        $this->addSql('DROP TABLE certifications');
        $this->addSql('DROP TABLE entity_attributes');
        $this->addSql('DROP TABLE event_attributes');
        $this->addSql('DROP TABLE event_registrations');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE gallery');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE modules');
        $this->addSql('DROP TABLE pages');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE services');
        $this->addSql('DROP TABLE system_settings');
        $this->addSql('DROP TABLE testimonials');
        $this->addSql('DROP TABLE translations');
        $this->addSql('DROP TABLE user_attributes');
        $this->addSql('DROP TABLE user_certifications');
        $this->addSql('DROP TABLE user_roles');
        $this->addSql('DROP TABLE user_type_attributes');
        $this->addSql('DROP TABLE user_types');
        $this->addSql('DROP TABLE users');
    }
}
