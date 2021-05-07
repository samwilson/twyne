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

    public function testGetPostList()
    {
        /** @var TagRepository $tagRepo */
        $tagRepo = $this->entityManager->getRepository(Tag::class);
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
        $this->assertSame(1, $tagRepo->countPosts($tag, $user));
        $posts = $tagRepo->findPosts($tag, $user);
        $this->assertGreaterThan(0, $posts[0]->getId());
    }
}
