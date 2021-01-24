<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210107080007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add contact to user.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD COLUMN contact_id INT NULL DEFAULT NULL');
        $this->addSql('UPDATE user SET contact_id = id');
        $this->addSql('ALTER TABLE user CHANGE COLUMN contact_id contact_id INT NOT NULL');
        $this->addSql(
            'ALTER TABLE user ADD CONSTRAINT FK_8D93D649E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)'
        );
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7A1254A ON user (contact_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E7A1254A');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7A1254A ON user');
        $this->addSql('ALTER TABLE user DROP contact_id');
    }
}
