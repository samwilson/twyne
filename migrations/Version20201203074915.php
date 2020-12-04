<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201203074915 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Add in-reply-to to posts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE post '
            . 'ADD in_reply_to_id INT DEFAULT NULL, '
            . 'ADD CONSTRAINT FK_POST_IN_REPLY_TO FOREIGN KEY (in_reply_to_id) REFERENCES post (id)'
        );
        $this->addSql('CREATE INDEX IDX_POST_IN_REPLY_TO ON post (in_reply_to_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_POST_IN_REPLY_TO');
        $this->addSql('DROP INDEX IDX_POST_IN_REPLY_TO ON post');
        $this->addSql('ALTER TABLE post DROP in_reply_to_id');
    }
}
