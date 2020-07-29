<?php

namespace Samwilson\Twyne\Tests;

use Samwilson\Twyne\Data\Feed;
use Samwilson\Twyne\Data\User;

class FeedTest extends TestCase
{

    /**
     * A Feed belongs to a Contact.
     */
    public function testExists()
    {
        $user = User::register('feedowner', 'feedowner@example.org', 'pwd123');
        $feed = new Feed();
        $feed->setContact($user->getContact());
        $feed->setType('twitter');
        $feed->setValue('samwilson');
        $feed->save();
        static::assertEmpty($feed->getItems());
    }
}
