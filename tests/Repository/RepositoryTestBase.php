<?php

namespace App\Tests\Repository;

use App\Entity\Post;
use App\Kernel;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class RepositoryTestBase extends KernelTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    protected static function getKernelClass()
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        // Set a fake clock time of 2020-11-15 07:36:41 and register all our classes that use the time() function.
        ClockMock::withClockMock(1605425801);
        ClockMock::register(self::class);
        ClockMock::register(PostRepository::class);
        ClockMock::register(TagRepository::class);
        ClockMock::register(Post::class);

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }
}
