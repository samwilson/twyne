<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testRegisterAndLogin()
    {
        $client = static::createClient();

        // Go to the login page.
        $client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main h1', 'Log in');

        // Posting to /login redirects back to /login.
        $client->submitForm('Log in', [], 'POST');
        $this->assertResponseRedirects('/login');

        // Incorrect username.
        $client->request('GET', '/login');
        $client->submitForm('Log in', [
            'username' => 'foo',
            '_csrf_token' => '123',
        ], 'POST');
        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorTextContains('.flashes .message.error', 'Incorrect username, password, and/or 2FA key.');

        // Go to register.
        $client->request('GET', '/register');
        $this->assertSelectorTextContains('main h1', 'Register');

        // Register a new user.
        $client->submitForm('Create account', [
            'username' => 'testuser-' . time(),
            'email' => 'test@example.org',
            'password' => 'test123',
            '_csrf_token' => '123',
        ], 'POST');
        $this->assertResponseRedirects('/');
    }
}
