<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\UserGroup;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210127102730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user groups.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE user_user_group ('
            . ' user_id INT NOT NULL,'
            . ' user_group_id INT NOT NULL,'
            . ' INDEX IDX_28657971A76ED395 (user_id),'
            . ' INDEX IDX_286579711ED93D47 (user_group_id),'
            . ' PRIMARY KEY(user_id, user_group_id)'
            . ' ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE user_group ('
            . ' id INT AUTO_INCREMENT NOT NULL,'
            . ' name VARCHAR(255) NOT NULL,'
            . ' PRIMARY KEY(id)'
            . ' ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE user_user_group'
            . ' ADD CONSTRAINT FK_286579711ED93D47 FOREIGN KEY (user_group_id)'
            . '     REFERENCES user_group (id) ON DELETE CASCADE,'
            . ' ADD CONSTRAINT FK_28657971A76ED395 FOREIGN KEY (user_id)'
            . '     REFERENCES user (id) ON DELETE CASCADE'
        );
        // Create a group.
        $publicGroupId = UserGroup::PUBLIC;
        $this->addSql("INSERT INTO user_group SET id = $publicGroupId, name = 'Public'");
        // Make sure the first user is a member of it.
        $this->addSql(
            "INSERT INTO user_user_group (user_id, user_group_id)"
            . " (SELECT id, $publicGroupId FROM user ORDER BY id LIMIT 1)"
        );
        $this->addSql(
            'ALTER TABLE post '
            . " ADD view_group_id INT NOT NULL DEFAULT $publicGroupId,"
            . ' ADD INDEX IDX_5A8A6C8DA3159F50 (view_group_id),'
            . ' ADD CONSTRAINT FK_5A8A6C8DA3159F50 FOREIGN KEY (view_group_id) REFERENCES user_group (id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DA3159F50');
        $this->addSql(
            'ALTER TABLE user_user_group'
            . ' DROP FOREIGN KEY FK_286579711ED93D47,'
            . ' DROP FOREIGN KEY FK_28657971A76ED395'
        );
        $this->addSql('DROP TABLE user_user_group');
        $this->addSql('DROP TABLE user_group');
        $this->addSql('ALTER TABLE post DROP view_group_id');
    }
}
