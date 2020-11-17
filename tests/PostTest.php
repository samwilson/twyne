<?php

namespace App\Tests;

use App\Entity\Contact;
use App\Entity\File;
use App\Entity\Post;
use App\Kernel;
use App\Repository\PostRepository;
use DateTime;
use DateTimeZone;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

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
        // Set a fake clock time of 2020-11-15 07:36:41 and register all our classes that use the time() function.
        ClockMock::withClockMock(1605425801);
        ClockMock::register(PostRepository::class);
        ClockMock::register(PostTest::class);
        ClockMock::register(Post::class);

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

    /**
     * A post's default date should be the current time.
     */
    public function testDefaultDate()
    {
        $post = new Post();
        $this->assertEquals('2020-11-15 07:36:41', $post->getDate()->format('Y-m-d H:i:s'));
    }

    /**
     * A post can be saved from data in a Request, including an uploaded file.
     *
     * @dataProvider provideSaveFromRequest()
     */
    public function testSaveFromRequest($postParams, $filepath, $title, $date)
    {
        /** @var PostRepository $postRepo */
        $postRepo = $this->entityManager->getRepository(Post::class);
        $request = new Request([], $postParams);
        $uploadedFile = new UploadedFile($filepath, basename($filepath));
        $post = new Post();
        $this->assertEquals('2020-11-15 07:36:41', $post->getDate()->format('Y-m-d H:i:s'));
        $postRepo->saveFromRequest($post, $request, $uploadedFile);
        $this->assertEquals($title, $post->getTitle());
        $this->assertEquals($date, $post->getDate()->format('Y-m-d H:i:s'));
    }

    public function provideSaveFromRequest()
    {
        return [
            [
                'postParams' => ['title' => 'Test title', 'author' => 'Bob'],
                'filepath' => __DIR__ . '/data/has_metadata.jpg',
                'title' => 'Test title',
                'date' => '2020-11-14 12:34:56',
            ],
            [
                'postParams' => ['author' => 'Bob'],
                'filepath' => __DIR__ . '/data/has_metadata.jpg',
                'title' => 'has metadata',
                'date' => '2020-11-14 12:34:56',
            ],
            [
                'postParams' => ['author' => 'Bob', 'date' => '2020-01-01 13:45:00'],
                'filepath' => __DIR__ . '/data/has_metadata.jpg',
                'title' => 'has metadata',
                'date' => '2020-01-01 13:45:00',
            ],
            [
                'postParams' => ['author' => 'Kev'],
                'filepath' => __DIR__ . '/data/no-metadata.jpg',
                'title' => 'no-metadata',
                'date' => '2020-11-15 07:36:41',
            ],
            [
                'postParams' => ['author' => 'Bob', 'timezone' => 'Australia/Perth'],
                'filepath' => __DIR__ . '/data/has_metadata.jpg',
                'title' => 'has metadata',
                'date' => '2020-11-14 04:34:56',
            ],
        ];
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
