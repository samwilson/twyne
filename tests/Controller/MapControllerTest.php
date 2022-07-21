<?php

namespace App\Tests;

use App\Tests\Controller\ControllerTestBase;
use App\Repository\TrackPointRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class MapControllerTest extends ControllerTestBase
{
    public function testKeyNotConfigured()
    {
        $client = static::createClient();
        $client->request('POST', '/overland?key=123', [], [], [], '{}');
        $actualResult = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('not configured', $actualResult['error']);
    }

    public function testNotAuthorized()
    {
        $client = static::createClient();
        $this->setOverlandKey($client);
        $client->request('POST', '/overland?key=wrongkey', [], [], [], '{}');
        $actualResult = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('unauthorized', $actualResult['error']);
    }

    private function setOverlandKey(KernelBrowser $client)
    {
        $key = '123abc';
        $this->createAccountAndLogIn($client);
        $settingsPage = $client->request('GET', '/settings');
        $client->submitForm('Save', [
            'settings[overland_key]' => $key,
            'token' => $settingsPage->filter('[name="token"]')->attr('value'),
        ], 'POST');
        $client->request('GET', '/settings');
        return $key;
    }

    /**
     * @dataProvider provideOverlandApiSubmission()
     */
    public function testOverlandApiSubmission($input, $result)
    {
        $client = static::createClient();
        $key = $this->setOverlandKey($client);
        $client->request('POST', '/overland?key=' . $key, [], [], [], $input);
        $actualResult = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($result, $actualResult);
    }

    public function provideOverlandApiSubmission()
    {
        return [
            'no data' => [
                null,
                [ 'result' => 'ok' ]
            ],
            'invalid json' => [
                '{ "invalid": "json", }',
                [ 'result' => 'error', 'error' => 4 ]
            ],
            'invalid locations' => [
                json_encode([ 'locations' => 'foo' ]),
                [ 'result' => 'ok' ]
            ],
            '1 good location' => [
                json_encode([ 'locations' => [
                    [
                        'geometry' => [ 'coordinates' => [ 10, 20 ] ],
                        'properties' => [ 'timestamp' => '2021-03-02 18:37:00 +0800' ],
                    ]
                ] ]),
                [ 'result' => 'ok' ]
            ],
        ];
    }

    public function testOverlandPointSaving()
    {
        $client = static::createClient();
        $key = $this->setOverlandKey($client);
        $client->request('POST', '/overland?key=' . $key, [], [], [], '{
        "locations": [
          {"geometry": {"coordinates": [40, 50]}, "properties": {"timestamp": "2021-03-03 17:00:00 +0800"}},
          {"geometry": {"coordinates": [50, 60]}, "properties": {"timestamp": "2021-03-03 18:00:00 +0800"}}
        ]
        }');
        /** @var TrackPointRepository $tpRepo */
        $tpRepo = self::$container->get(TrackPointRepository::class);
        $this->assertSame(2, $tpRepo->count([]));
        $first = $tpRepo->findOneBy([]);
        $this->assertSame('2021-03-03 09:00', $first->getTimestamp()->format('Y-m-d H:i'));
        $this->assertSame(50.0, $first->getLocation()->getLatitude());
        $this->assertSame(40.0, $first->getLocation()->getLongitude());
    }
}
