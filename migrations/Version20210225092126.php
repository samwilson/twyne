<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210225092126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add location_point table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE location_point ( '
            . ' id INT AUTO_INCREMENT NOT NULL,'
            . " location POINT NOT NULL COMMENT '(DC2Type:point)',"
            . ' timestamp DATETIME NOT NULL,'
            . ' PRIMARY KEY(id)'
            . ' ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE `location_point`'
            . ' ADD SPATIAL `IDX_LP_LOCATION` (`location`),'
            . ' ADD INDEX  `IDX_LP_TIMESTAMP` (`timestamp`)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE location_point');
    }
}
