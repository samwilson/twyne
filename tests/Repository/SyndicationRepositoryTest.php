<?php

namespace App\Test\Repository;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Tests\Repository\RepositoryTestBase;
use Symfony\Component\HttpFoundation\Request;

class SyndicationRepositoryTest extends RepositoryTestBase
{
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
        $post = $postRepo->createNew();
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
