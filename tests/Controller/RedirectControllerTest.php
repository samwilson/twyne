<?php

namespace App\Tests\Controller;

class RedirectControllerTest extends ControllerTestBase
{
    public function testRedirects()
    {
        $client = static::createClient();
        $this->createAccountAndLogIn($client);

        // Test a 410 Gone response.
        $client->request('GET', '/redirect');
        $client->submitForm('Save', [
            'path' => '/foo',
            'destination' => '',
            'status' => '410',
        ]);
        $client->followRedirect();
        $this->assertSelectorExists('table.redirects tbody tr');
        $client->request('GET', '/foo');
        $this->assertResponseStatusCodeSame(410);

        // Test an external 301 redirect.
        $client->request('GET', '/redirect');
        $client->submitForm('Save', [
            'path' => '/foo2',
            'destination' => 'https://example.org/bar/',
            'status' => '301',
        ]);
        $client->request('GET', '/foo2');
        $this->assertResponseRedirects('https://example.org/bar/', 301);

        // Test an internal 302 redirect.
        $client->request('GET', '/redirect');
        $client->submitForm('Save', [
            'path' => '/foo3',
            'destination' => '/baz',
            'status' => '302',
        ]);
        $client->request('GET', '/foo3');
        $this->assertResponseRedirects('/baz', 302);
    }
}
