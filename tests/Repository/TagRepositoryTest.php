<?php

namespace App\Tests\Repository;

use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use Symfony\Component\HttpFoundation\Request;

class TagRepositoryTest extends RepositoryTestBase
{

    /** @var TagRepository */
    private $tagRepo;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var TagRepository $tagRepo */
        $this->tagRepo = $this->entityManager->getRepository(Tag::class);
    }

    public function testGetPostList()
    {
        /** @var PostRepository $postRepo */
        $postRepo = $this->entityManager->getRepository(Post::class);

        $tag = new Tag();
        $tag->setTitle('Tag 1');
        $this->entityManager->persist($tag);
        $privateGroup = new UserGroup();
        $privateGroup->setName('Private');
        $this->entityManager->persist($privateGroup);
        $this->entityManager->flush();

        // Create two posts tagged the same, one of which is private.
        $publicPost = new Post();
        $publicPostData = ['tags' => ['Tag 1'], 'view_group' => UserGroup::PUBLIC, 'author' => 1];
        $postRepo->saveFromRequest($publicPost, new Request([], $publicPostData));
        $privatePost = new Post();
        $privatePostData = ['tags' => ['Tag 1'], 'view_group' => $privateGroup->getId(), 'author' => 1];
        $postRepo->saveFromRequest($privatePost, new Request([], $privatePostData));

        // Make sure the user is only in the public group.
        $user = new User();

        // Check that only one post is returned.
        $this->assertSame(1, $this->tagRepo->countPosts($tag, $user));
        $posts = $this->tagRepo->findPosts($tag, $user);
        $this->assertGreaterThan(0, $posts[0]->getId());
    }

    public function testMerge()
    {
        // Start with nothing.
        $this->assertCount(0, $this->tagRepo->findAll());

        // Create two tags.
        $tag1 = $this->tagRepo->createNew('Tag One');
        $tag2 = $this->tagRepo->createNew('Tag Two');

        // Create four posts: one in each tag, one in both, and one in none.
        $post1 = $this->getTestPost();
        $post1->addTag($tag1);
        $post2 = $this->getTestPost();
        $post2->addTag($tag2);
        $post3 = $this->getTestPost();
        $post3->addTag($tag1);
        $post3->addTag($tag2);
        $post4 = $this->getTestPost();
        $this->entityManager->persist($post1);
        $this->entityManager->persist($post2);
        $this->entityManager->persist($post3);
        $this->entityManager->persist($post4);
        $this->entityManager->flush();

        // Make sure all is set up correctly.
        $this->assertCount(2, $this->tagRepo->findAll());
        $this->assertCount(4, $this->postRepo->findAll());
        $this->assertCount(1, $post1->getTags());
        $this->assertCount(1, $post2->getTags());
        $this->assertCount(2, $post3->getTags());
        $this->assertEmpty($post4->getTags());
        $this->assertSame(2, $this->tagRepo->countAllPostsInAny([$tag1]));
        $this->assertSame(2, $this->tagRepo->countAllPostsInAny([$tag2]));
        $this->assertSame(3, $this->tagRepo->countAllPostsInAny([$tag1, $tag2]));
        $this->assertSame(1, $this->tagRepo->countAllPostsInBoth($tag1, $tag2));

        // Merge the tags, and check that everything's ended up correctly.
        $this->tagRepo->merge($tag1, $tag2);
        $this->assertCount(1, $this->tagRepo->findAll());
        $this->assertCount(4, $this->postRepo->findAll());
        $this->assertSame(3, $this->tagRepo->countPosts($tag2));
    }
}
