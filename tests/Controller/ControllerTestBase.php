<?php

namespace App\Tests\Controller;

use App\Entity\Post;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ControllerTestBase extends WebTestCase
{
    public function setUp(): void
    {
        // Set a fake clock time of 2020-11-15 07:36:41 and register all our classes that use the time() function.
        ClockMock::withClockMock(1605425801);
        ClockMock::register(Post::class);
    }

    /**
     * Create a new account and log in.
     * Should only be called once per test, and the account created will be a site admin.
     */
    protected function createAccountAndLogIn(KernelBrowser $client)
    {
        $username = 'testuser-' . time();
        $password = 'test123';
        $client->request('GET', '/register');
        $client->submitForm('Create account', [
            'username' => $username,
            'email' => 'test@example.org',
            'password' => $password,
            '_csrf_token' => '123',
        ], 'POST');
        $client->request('GET', '/login');
        $client->submitForm('Log in', [
            'username' => $username,
            'password' => $password,
        ]);
        $client->request('GET', '/contacts');
        // There will only be one edit link.
        $client->clickLink('Edit');
        $client->submitForm('Save', ['new_group' => 'Private'], 'POST');
    }
}
