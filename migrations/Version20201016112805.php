<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201016112805 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Add deletion cascade to files.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F36104B89032C');
        $this->addSql('DROP INDEX IDX_8C9F36104B89032C ON file');
        $this->addSql(
            'ALTER TABLE file
            ADD CONSTRAINT FK_8C9F36104B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE'
        );
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D93CB796C');
        $this->addSql(
            'ALTER TABLE post
            ADD CONSTRAINT FK_5A8A6C8D93CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON DELETE SET NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F36104B89032C');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36104B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('CREATE INDEX IDX_8C9F36104B89032C ON file (post_id)');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D93CB796C');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D93CB796C FOREIGN KEY (file_id) REFERENCES file (id)');
    }
}
