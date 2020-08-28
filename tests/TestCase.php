<?php

namespace Samwilson\Twyne\Tests;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Samwilson\Twyne\Database;

abstract class TestCase extends PhpUnitTestCase
{

    /** @var Database */
    protected $db;

    public function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->removeTables();
        $this->db->install();
    }

    public function removeTables(): void
    {
        $this->db->query('SET foreign_key_checks=off');
        $this->db->query('DROP TABLE IF EXISTS `settings`');
        $this->db->query('DROP TABLE IF EXISTS `users`');
        $this->db->query('DROP TABLE IF EXISTS `contacts`');
        $this->db->query('DROP TABLE IF EXISTS `posts`');
        $this->db->query('DROP TABLE IF EXISTS `feeds`');
        $this->db->query('DROP TABLE IF EXISTS `feed_items`');
        $this->db->query('SET foreign_key_checks=on');
    }
}
