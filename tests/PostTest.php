<?php

namespace App\Tests;

use App\Entity\Contact;
use App\Entity\Post;
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PostTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    protected static function getKernelClass()
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testAuthor()
    {
        // Test author.
        $author = new Contact();
        $author->setName('Bob');
        $this->entityManager->persist($author);

        // Test post.
        $post = new Post();
        $post->setAuthor($author);
        $this->entityManager->persist($post);

        $this->entityManager->flush();

        // Make sure one post was saved.
        $postRepo = $this->entityManager->getRepository(Post::class);
        $this->assertSame(1, $postRepo->count([]));
        $this->assertSame($postRepo->findAll()[0]->getAuthor()->getName(), 'Bob');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
