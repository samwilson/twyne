<?php

namespace App\Tests\Repository;

use App\Repository\RedirectRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;

class RedirectRepositoryTest extends RepositoryTestBase
{
    /** @var RedirectRepository */
    private $redirectRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redirectRepo = self::$container->get(RedirectRepository::class);
    }

    /**
     * The path must always start with a slash.
     */
    public function testPath(): void
    {
        $redirect = $this->redirectRepo->saveFromRequest(new Request([], [
            'path' => 'foo',
            'destination' => '/foobar',
            'status' => '301',
        ]));
        $this->assertSame('/foo', $redirect->getPath());
    }

    public function testDuplicates(): void
    {
        $this->redirectRepo->saveFromRequest(new Request([], [
            'path' => 'foo',
            'destination' => '/foobar',
            'status' => '301',
        ]));
        $this->expectException(UniqueConstraintViolationException::class);
        $this->redirectRepo->saveFromRequest(new Request([], [
            'path' => 'foo',
            'destination' => '/foobar',
            'status' => '301',
        ]));
    }
}
