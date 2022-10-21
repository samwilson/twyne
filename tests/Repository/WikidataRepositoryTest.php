<?php

namespace App\Test\Repository;

use App\Repository\WikidataRepository;
use App\Tests\Repository\RepositoryTestBase;

class WikidataRepositoryTest extends RepositoryTestBase
{
    public function testGetData()
    {
        /** @var WikidataRepository $wikidataRepo */
        $wikidataRepo = self::$container->get(WikidataRepository::class);
        // Redirected item.
        $data = $wikidataRepo->getData('Q73007800');
        $this->assertSame('Q5501480', $data['id']);
    }
}
