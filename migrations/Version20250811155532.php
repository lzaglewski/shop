<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811155532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE settings RENAME INDEX uniq_e545a0c5a94d0a63 TO UNIQ_E545A0C55FA1E697');
        $this->addSql('ALTER TABLE users ADD contact_number VARCHAR(20) DEFAULT NULL, ADD delivery_street VARCHAR(255) DEFAULT NULL, ADD delivery_postal_code VARCHAR(10) DEFAULT NULL, ADD delivery_city VARCHAR(100) DEFAULT NULL, ADD billing_company_name VARCHAR(255) DEFAULT NULL, ADD billing_street VARCHAR(255) DEFAULT NULL, ADD billing_postal_code VARCHAR(10) DEFAULT NULL, ADD billing_city VARCHAR(100) DEFAULT NULL, ADD billing_tax_id VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP contact_number, DROP delivery_street, DROP delivery_postal_code, DROP delivery_city, DROP billing_company_name, DROP billing_street, DROP billing_postal_code, DROP billing_city, DROP billing_tax_id');
        $this->addSql('ALTER TABLE settings RENAME INDEX uniq_e545a0c55fa1e697 TO UNIQ_E545A0C5A94D0A63');
    }
}
