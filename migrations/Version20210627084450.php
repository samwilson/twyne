<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210627084450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add 2fa secret to users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD 2fa_secret VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP 2fa_secret');
    }
}
