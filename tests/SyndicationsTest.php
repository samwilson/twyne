<?php

namespace App\Tests;

use App\Entity\Post;
use App\Kernel;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class SyndicationsTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    protected static function getKernelClass()
    {
        return Kernel::class;
    }

    public function setUp()
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * A post can be syndicated to a URL.
     */
    public function testAddNew()
    {
        /** @var PostRepository $postRepo */
        $postRepo = $this->entityManager->getRepository(Post::class);

        // Add a new syndication.
        $request1 = new Request([], [
            'author' => 'test',
            'new_syndication' => [ 'label' => 'syn label', 'url' => 'syn URL'],
        ]);
        $post = new Post();
        $this->assertEmpty($post->getSyndications());
        $postRepo->saveFromRequest($post, $request1);
        $this->assertSame('syn label', $post->getSyndications()->first()->getLabel());

        // Then delete it.
        $request2 = new Request([], [
            'syndications_to_delete' => [ $post->getSyndications()->first()->getId() ],
        ]);
        $postRepo->saveFromRequest($post, $request2);
        $this->assertCount(0, $post->getSyndications());
    }
}
