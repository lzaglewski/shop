<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251025100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create gallery_images table for homepage gallery management';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE gallery_images (
                id INT AUTO_INCREMENT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                position INT DEFAULT 0 NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                INDEX idx_gallery_position (position)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE gallery_images');
    }
}
