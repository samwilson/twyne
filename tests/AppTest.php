<?php

namespace Samwilson\Twyne\Tests;

use Samwilson\Twyne\App;

class AppTest extends TestCase
{

    /**
     * @dataProvider camelcaseTests
     */
    public function testCamelcase($from, $to)
    {
        static::assertEquals($to, App::camelcase($from));
    }

    public function camelcaseTests()
    {
        return [
            ['foo', 'Foo'],
            ['foo_bar', 'FooBar'],
            ['foo BAR', 'FooBar'],
        ];
    }
}
