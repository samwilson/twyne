<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200926023839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add contacts and post authors.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE contact (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description_public LONGTEXT DEFAULT NULL,
                description_private LONGTEXT DEFAULT NULL,
                homepage LONGTEXT DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE post ADD author_id INT NOT NULL');
        // Create a contact for the first user (the administrator).
        $newContactId = 1;
        $this->addSql(
            "INSERT INTO contact (id, name)
            SELECT $newContactId, username FROM user WHERE id = (SELECT MIN(id) FROM user)"
        );
        // Update all existing posts to have that contact as author.
        $this->addSql("UPDATE post SET post.author_id = $newContactId");
        // Add FK to contact.
        $this->addSql(
            'ALTER TABLE post
            ADD CONSTRAINT FK_5A8A6C8DF675F31B FOREIGN KEY (author_id) REFERENCES contact (id)'
        );
        $this->addSql('CREATE INDEX IDX_5A8A6C8DF675F31B ON post (author_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DF675F31B');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP INDEX IDX_5A8A6C8DF675F31B ON post');
        $this->addSql('ALTER TABLE post DROP author_id');
    }
}
