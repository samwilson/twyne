<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201223000735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add syndications.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE syndication (
            id INT AUTO_INCREMENT NOT NULL,
            post_id INT NOT NULL,
            url VARCHAR(255) NOT NULL,
            label VARCHAR(255) NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT FK_193309574B89032C FOREIGN KEY (post_id) REFERENCES post (id),
            UNIQUE INDEX UNIQ_SYNDICATION_URL (url)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE syndication');
    }
}
