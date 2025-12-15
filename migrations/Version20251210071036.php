<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210071036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add medical_certificates and caci_access_logs tables for new CACI workflow with file upload';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE caci_access_logs (id INT AUTO_INCREMENT NOT NULL, accessed_by_id INT NOT NULL, target_user_id INT NOT NULL, certificate_id INT DEFAULT NULL, action VARCHAR(20) NOT NULL, access_context VARCHAR(50) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, accessed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5D1342A4AFA04F13 (accessed_by_id), INDEX IDX_5D1342A499223FFD (certificate_id), INDEX idx_caci_access_date (accessed_at), INDEX idx_caci_target_user (target_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE medical_certificates (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, validated_by_id INT DEFAULT NULL, encrypted_file_path VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, expiry_date DATE NOT NULL, status VARCHAR(20) NOT NULL, consent_given TINYINT(1) NOT NULL, uploaded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', validated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', rejection_reason LONGTEXT DEFAULT NULL, scheduled_deletion_date DATE DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A8DBD030A76ED395 (user_id), INDEX IDX_A8DBD030C69DE5E5 (validated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caci_access_logs ADD CONSTRAINT FK_5D1342A4AFA04F13 FOREIGN KEY (accessed_by_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE caci_access_logs ADD CONSTRAINT FK_5D1342A46C066AFE FOREIGN KEY (target_user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE caci_access_logs ADD CONSTRAINT FK_5D1342A499223FFD FOREIGN KEY (certificate_id) REFERENCES medical_certificates (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE medical_certificates ADD CONSTRAINT FK_A8DBD030A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE medical_certificates ADD CONSTRAINT FK_A8DBD030C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caci_access_logs DROP FOREIGN KEY FK_5D1342A4AFA04F13');
        $this->addSql('ALTER TABLE caci_access_logs DROP FOREIGN KEY FK_5D1342A46C066AFE');
        $this->addSql('ALTER TABLE caci_access_logs DROP FOREIGN KEY FK_5D1342A499223FFD');
        $this->addSql('ALTER TABLE medical_certificates DROP FOREIGN KEY FK_A8DBD030A76ED395');
        $this->addSql('ALTER TABLE medical_certificates DROP FOREIGN KEY FK_A8DBD030C69DE5E5');
        $this->addSql('DROP TABLE caci_access_logs');
        $this->addSql('DROP TABLE medical_certificates');
    }
}
