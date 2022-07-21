<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201127035313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add point location to posts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE post ADD location POINT DEFAULT NULL COMMENT '(DC2Type:point)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP location');
    }
}
