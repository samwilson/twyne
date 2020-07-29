<?php

namespace Samwilson\Twyne\Tests;

use Samwilson\Twyne\Data\Post;
use Samwilson\Twyne\Data\User;

class PostTest extends TestCase
{

    /**
     */
    public function testBasics()
    {
        // Create a user to test with.
        $user = User::register('admin', 'admin@example.org', 'pwd123');
        // Create a minimal post belonging to that user.
        $post = new Post();
        $post->setUser($user->getId());
        $post->setAuthor($user->getContact());
        $post->setBody('Lorem');
        $post->save();
        $this->assertEquals('Lorem', $post->getBody());
        $this->assertEquals($user->getId(), $post->getUser()->getId());
        $this->assertSame(1, $post->getId());
        $post->setBody('New body');
        $this->assertEquals('New body', $post->getBody());
        $this->assertSame(1, $post->getId());
        $post->save();
        $this->assertEquals('New body', $post->getBody());
        $this->assertSame(1, $post->getId());
    }
}
