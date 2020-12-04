<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200918094712 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Initial installation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE setting (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                value JSON NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE post (
                id INT AUTO_INCREMENT NOT NULL,
                date DATETIME NOT NULL,
                title VARCHAR(255) DEFAULT NULL,
                body LONGTEXT DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE user (
                id INT AUTO_INCREMENT NOT NULL,
                username VARCHAR(180) NOT NULL,
                email VARCHAR(255) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                roles JSON NOT NULL,
                UNIQUE INDEX UNIQ_8D93D649F85E0677 (username),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE setting');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE user');
    }
}
