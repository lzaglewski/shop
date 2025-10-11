<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011094031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make order_items.product_id nullable with ON DELETE SET NULL to allow product deletion';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB04584665A');
        $this->addSql('ALTER TABLE order_items CHANGE product_id product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB04584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB04584665A');
        $this->addSql('ALTER TABLE order_items CHANGE product_id product_id INT NOT NULL');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB04584665A FOREIGN KEY (product_id) REFERENCES products (id)');
    }
}
