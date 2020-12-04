<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201027095911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tags to posts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE post_tag (
                post_id INT NOT NULL,
                tag_id INT NOT NULL,
                INDEX IDX_POST_TAG_POST_ID (post_id),
                INDEX IDX_POST_TAG_TAG_ID (tag_id),
                PRIMARY KEY(post_id, tag_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE tag (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                wikidata VARCHAR(15) DEFAULT NULL,
                description LONGTEXT DEFAULT NULL,
                UNIQUE INDEX UNIQ_TAG_TITLE (title),
                UNIQUE INDEX UNIQ_TAG_WIKIDATA (wikidata),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE post_tag '
            . 'ADD CONSTRAINT FK_POST_TAG_POST_ID FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE,'
            . 'ADD CONSTRAINT FK_POST_TAG_TAG_ID FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE post_tag '
            . 'DROP FOREIGN KEY FK_POST_TAG_POST_ID,'
            . 'DROP FOREIGN KEY FK_POST_TAG_TAG_ID'
        );
        $this->addSql('DROP TABLE post_tag');
        $this->addSql('DROP TABLE tag');
    }
}
