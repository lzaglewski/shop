<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251025125643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove marketing consent column from user_cookie_consents table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_cookie_consents DROP COLUMN marketing');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_cookie_consents ADD COLUMN marketing TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
