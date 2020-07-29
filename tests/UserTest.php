<?php

namespace Samwilson\Twyne\Tests;

use Exception;
use Samwilson\Twyne\Data\User;

class UserTest extends TestCase
{

    /**
     * @testdox A user can register with their name, email, and password.
     */
    public function testRegister()
    {
        $user = User::register('user1', 'test@example.com', 'foo');
        static::assertEquals('user1', $user->getContact()->getName());
        static::assertGreaterThan(0, $user->getId());

        // Try registering the same username, and get an error.
        try {
            User::register('user1', 'test@example.com', 'bar');
        } catch (Exception $exception) {
            static::assertSame('user-exists', $exception->getMessage());
        }
    }

    public function testLogin()
    {
        $user = User::register('user', 'test@example.com', 'foo');
        static::assertTrue($user->checkPassword('foo'));
        static::assertFalse($user->checkPassword('bar'));
        static::assertFalse($user->checkPassword('bar'));
    }

    public function testRemind()
    {
        $user = User::register('user', 'test@example.org', 'foo');
        static::assertFalse($user->checkReminderToken('blah'));
        $tok = $user->getReminderToken();
        static::assertIsString($tok);
        // A non-token fails.
        static::assertFalse($user->checkReminderToken('blah'));
        // The token succeeds.
        static::assertTrue($user->checkReminderToken($tok));
        // The token fails on any subsequent try.
        static::assertFalse($user->checkReminderToken($tok));
        //
    }
}
