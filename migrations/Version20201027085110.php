<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201027085110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add URL to posts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD url LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP url');
    }
}
