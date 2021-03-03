<?php

namespace App\Tests;

use App\Repository\LocationPointRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use phpDocumentor\Reflection\Location;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LocationPointTest extends WebTestCase
{
    /**
     * @dataProvider provideOverlandApiSubmission()
     */
    public function testOverlandApiSubmission($input, $result)
    {
        $client = static::createClient();
        $client->request('POST', '/overland', [], [], [], $input);
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
        $client->request('POST', '/overland', [], [], [], '{
        "locations": [
          {"geometry": {"coordinates": [40, 50]}, "properties": {"timestamp": "2021-03-03 17:00:00 +0800"}},
          {"geometry": {"coordinates": [50, 60]}, "properties": {"timestamp": "2021-03-03 18:00:00 +0800"}}
        ]
        }');
        /** @var LocationPointRepository $lpRepo */
        $lpRepo = self::$container->get(LocationPointRepository::class);
        $this->assertSame(2, $lpRepo->count([]));
        $first = $lpRepo->findOneBy([]);
        $this->assertSame('2021-03-03 09:00', $first->getTimestamp()->format('Y-m-d H:i'));
        $this->assertSame(50.0, $first->getLocation()->getLatitude());
        $this->assertSame(40.0, $first->getLocation()->getLongitude());
    }
}
