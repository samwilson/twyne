<?php

namespace App\Tests;

use App\Entity\Contact;
use App\Entity\File;
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

    public function testFile()
    {
        $post = new Post();
        $this->entityManager->persist($post);
        $author = new Contact();
        $author->setName('Bob');
        $this->entityManager->persist($author);
        $post->setAuthor($author);

        $file = new File();
        $file->setSize(100);
        $file->setMimeType('foo');
        $file->setChecksum('12345');
        $this->entityManager->persist($file);

        $post->setFile($file);
        $this->entityManager->persist($file);

        $this->entityManager->flush();

        $this->assertSame($file, $post->getFile());
        $this->assertSame($file->getPost(), $post);

        // Deleting the post should delete the file as well.
        $this->entityManager->remove($post);
        $this->entityManager->flush();
        $this->assertNull($post->getId());
        $this->assertNull($file->getId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
