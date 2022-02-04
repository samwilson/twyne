<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210925004247 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Add redirects.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE redirect (
                id INT AUTO_INCREMENT NOT NULL,
                path VARCHAR(255) NOT NULL,
                destination VARCHAR(255) DEFAULT NULL,
                status INT NOT NULL,
                PRIMARY KEY(id),
                UNIQUE INDEX UNIQ_REDIRECT_PATH (path)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE redirect');
    }
}
