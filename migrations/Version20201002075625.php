<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201002075625 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Add files.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE file (
                id INT AUTO_INCREMENT NOT NULL,
                post_id INT NOT NULL,
                size INT NOT NULL,
                mime_type VARCHAR(255) NOT NULL,
                checksum VARCHAR(255) NOT NULL,
                INDEX IDX_8C9F36104B89032C (post_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36104B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE file ADD UNIQUE INDEX UNIQ_8C9F36104B89032C (post_id)');

        $this->addSql('ALTER TABLE post ADD file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D93CB796C FOREIGN KEY (file_id) REFERENCES file (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A8A6C8D93CB796C ON post (file_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D93CB796C');
        $this->addSql('DROP INDEX UNIQ_5A8A6C8D93CB796C ON post');
        $this->addSql('ALTER TABLE post DROP file_id');
        $this->addSql('DROP TABLE file');
    }
}
