<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250716000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create settings table for homepage configuration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE settings (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, setting_key VARCHAR(255) NOT NULL, setting_value VARCHAR(1000) DEFAULT NULL, UNIQUE INDEX UNIQ_E545A0C5A94D0A63 (setting_key), INDEX IDX_E545A0C512469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE settings ADD CONSTRAINT FK_E545A0C512469DE2 FOREIGN KEY (category_id) REFERENCES product_categories (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE settings DROP FOREIGN KEY FK_E545A0C512469DE2');
        $this->addSql('DROP TABLE settings');
    }
}