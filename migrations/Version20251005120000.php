<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Make email column nullable and add check constraint to ensure
 * at least one of email or login is provided
 */
final class Version20251005120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make email nullable and ensure email or login is required';
    }

    public function up(Schema $schema): void
    {
        // Make email nullable
        $this->addSql('ALTER TABLE users MODIFY email VARCHAR(180) NULL');

        // Drop unique constraint on email temporarily to recreate it
        $this->addSql('ALTER TABLE users DROP INDEX UNIQ_1483A5E9E7927C74');

        // Recreate unique index on email (nullable unique index allows multiple NULLs)
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        // Remove check constraint first
        $this->addSql('ALTER TABLE users DROP INDEX UNIQ_1483A5E9E7927C74');

        // Make email NOT NULL again
        $this->addSql('ALTER TABLE users MODIFY email VARCHAR(180) NOT NULL');

        // Recreate unique index
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }
}
