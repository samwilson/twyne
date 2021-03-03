<?php

namespace App\Tests\Repository;

use App\Kernel;
use App\Entity\Post;
use App\Entity\Contact;
use App\Repository\TagRepository;
use App\Repository\PostRepository;
use Symfony\Bridge\PhpUnit\ClockMock;
use App\Test\Repository\PostRepositoryTest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class RepositoryTestBase extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /** @var PostRepository */
    protected $postRepo;

    protected static function getKernelClass()
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        // Set a fake clock time of 2020-11-15 07:36:41 and register all our classes that use the time() function.
        ClockMock::withClockMock(1605425801);
        ClockMock::register(self::class);
        ClockMock::register(Post::class);
        ClockMock::register(PostRepository::class);
        ClockMock::register(PostRepositoryTest::class);
        ClockMock::register(TagRepository::class);

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->postRepo = self::$container->get(PostRepository::class);
    }

    protected function getTestPost(): Post
    {
        $post = $this->postRepo->createNew();
        $this->entityManager->persist($post);
        $author = new Contact();
        $author->setName('Bob');
        $this->entityManager->persist($author);
        $post->setAuthor($author);
        return $post;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
