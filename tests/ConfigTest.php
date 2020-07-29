<?php

namespace Samwilson\Twyne\Tests;

use PHPUnit\Framework\TestCase;
use Samwilson\Twyne\Config;

class ConfigTest extends TestCase
{

    public function testBasics()
    {
        $config = new Config(__DIR__ . '/../config.example.php');
        $this->assertEquals(dirname(__DIR__), $config->appDir());
        $this->assertEquals('twyne', $config->databaseName());
    }
}
