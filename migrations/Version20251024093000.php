<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251024093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables for user cookie consents and audit logs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE user_cookie_consents (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                analytics TINYINT(1) DEFAULT 0 NOT NULL,
                marketing TINYINT(1) DEFAULT 0 NOT NULL,
                personalization TINYINT(1) DEFAULT 0 NOT NULL,
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX uniq_cookie_consent_user (user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
        $this->addSql('ALTER TABLE user_cookie_consents ADD CONSTRAINT FK_CONSENT_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        $this->addSql("
            CREATE TABLE cookie_consent_logs (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT DEFAULT NULL,
                preferences LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
                ip_hash VARCHAR(64) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                action VARCHAR(32) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_consent_log_user (user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
        $this->addSql('ALTER TABLE cookie_consent_logs ADD CONSTRAINT FK_CONSENT_LOG_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cookie_consent_logs DROP FOREIGN KEY FK_CONSENT_LOG_USER');
        $this->addSql('ALTER TABLE user_cookie_consents DROP FOREIGN KEY FK_CONSENT_USER');
        $this->addSql('DROP TABLE cookie_consent_logs');
        $this->addSql('DROP TABLE user_cookie_consents');
    }
}
