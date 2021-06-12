<?php

namespace App\Test\Repository;

use App\Entity\Contact;
use App\Entity\File;
use App\Entity\Post;
use App\Entity\UserGroup;
use App\Kernel;
use App\Repository\PostRepository;
use App\Repository\UserGroupRepository;
use App\Repository\UserRepository;
use App\Tests\Repository\RepositoryTestBase;
use CrEOF\Spatial\PHP\Types\Geometry\Point;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class PostRepositoryTest extends RepositoryTestBase
{

    /** @var PostRepository */
    protected $postRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postRepo = self::$container->get(PostRepository::class);
    }

    public function testAuthor()
    {
        // Test author.
        $author = new Contact();
        $author->setName('Bob');
        $this->entityManager->persist($author);

        // Test post.
        $post = $this->postRepo->createNew();
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
        $post = $this->postRepo->createNew();
        $this->assertEquals('2020-11-15 07:36:41', $post->getDate()->format('Y-m-d H:i:s'));
    }

    /**
     * A post can be saved from data in a Request, including an uploaded file.
     *
     * @dataProvider provideSaveFromRequest()
     */
    public function testSaveFromRequest($postParams, $filepath, $title, $date, $location = null)
    {
        /** @var PostRepository $postRepo */
        $postRepo = $this->entityManager->getRepository(Post::class);
        $request = new Request([], $postParams);
        $uploadedFile = new UploadedFile($filepath, basename($filepath));
        $post = $this->postRepo->createNew();
        $this->assertEquals('2020-11-15 07:36:41', $post->getDate()->format('Y-m-d H:i:s'));
        $postRepo->saveFromRequest($post, $request, $uploadedFile);
        $this->assertEquals($title, $post->getTitle());
        $this->assertEquals($date, $post->getDate()->format('Y-m-d H:i:s'));
        $this->assertequals($location, $post->getLocation());
    }

    public function provideSaveFromRequest()
    {
        return [
            [
                'postParams' => ['title' => 'Test title', 'author' => 'Bob', 'latitude' => '10', 'longitude' => '-20'],
                'filepath' => dirname(__DIR__) . '/data/has_metadata.jpg',
                'title' => 'Test title',
                'date' => '2020-11-14 12:34:56',
                'location' => new Point(-20, 10),
            ],
            [
                'postParams' => ['author' => 'Bob'],
                'filepath' => dirname(__DIR__) . '/data/has_metadata.jpg',
                'title' => 'has metadata',
                'date' => '2020-11-14 12:34:56',
            ],
            [
                'postParams' => ['author' => 'Bob', 'date' => '2020-01-01 13:45:00'],
                'filepath' => dirname(__DIR__) . '/data/has_metadata.jpg',
                'title' => 'has metadata',
                'date' => '2020-01-01 13:45:00',
            ],
            [
                'postParams' => ['author' => 'Kev'],
                'filepath' => dirname(__DIR__) . '/data/no-metadata.jpg',
                'title' => 'no-metadata',
                'date' => '2020-11-15 07:36:41',
            ],
            [
                'postParams' => ['author' => 'Bob', 'timezone' => 'Australia/Perth'],
                'filepath' => dirname(__DIR__) . '/data/has_metadata.jpg',
                'title' => 'has metadata',
                'date' => '2020-11-14 04:34:56',
            ],
        ];
    }

    public function testFile()
    {
        $post = $this->postRepo->createNew();
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

    public function testPermissions()
    {
        /** @var UserRepository $userRepo */
        $userRepo = self::$container->get(UserRepository::class);
        /** @var UserGroupRepository $groupRepo */
        $groupRepo = self::$container->get(UserGroupRepository::class);

        // Two test users and one test group. The users will already be in the default Public group.
        $user1 = $userRepo->findOrCreate('User One');
        $user2 = $userRepo->findOrCreate('User Two');
        $group = $groupRepo->findOrCreate('Other Group');
        $user1->addGroup($group);
        $this->entityManager->flush();
        $this->assertTrue($user1->isInGroup($group));
        $this->assertFalse($user2->isInGroup($group));

        // Test post.
        $post = $this->postRepo->createNew();
        $post->setAuthor($user1->getContact());
        $post->setViewGroup($group);
        $this->entityManager->persist($post);
        $this->entityManager->flush();
        $this->assertTrue($post->canBeViewedBy($user1));
        $this->assertFalse($post->canBeViewedBy($user2));
    }

    public function testAbortedSave()
    {
        $postRepo = $this->entityManager->getRepository(Post::class);

        // Save a post that works.
        $request1 = new Request([], ['author' => 'Test', 'view_group' => UserGroup::PUBLIC]);
        $post1 = new Post();
        $this->assertSame(null, $post1->getId());
        $postRepo->saveFromRequest($post1, $request1);
        $this->assertNotNull($post1->getId());

        // Then save one that fails.
        $request2 = new Request([], ['author' => 'Test', 'view_group' => UserGroup::PUBLIC]);
        $post2 = new Post();
        $this->assertNull($post2->getId());
        try {
            $uploadedFile = new UploadedFile('/non/existing/file.png', 'file.png');
            $postRepo->saveFromRequest($post2, $request2, $uploadedFile);
        } catch (FileNotFoundException $exception) {
        }
        $this->assertNull($post2->getId());
    }
}
