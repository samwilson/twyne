<?php

namespace App\Tests\Repository;

use App\Entity\Contact;
use App\Entity\File;
use App\Entity\Post;
use App\Repository\ContactRepository;
use App\Repository\FileRepository;
use App\Repository\PostRepository;

class ContactRepositoryTest extends RepositoryTestBase
{
    public function testGetRecent()
    {
        /** @var ContactRepository $fileRepo */
        $contactRepo = $this->entityManager->getRepository(Contact::class);
        /** @var PostRepository $fileRepo */
        $postRepo = $this->entityManager->getRepository(Post::class);

        // Create an author and give them a few posts.
        $author = new Contact();
        $author->setName('Bob');
        $this->entityManager->persist($author);
        for ($i = 0; $i < 10; $i++) {
            $post = $postRepo->createNew();
            $post->setAuthor($author);
            $this->entityManager->persist($post);
        }
        $this->entityManager->flush();

        $posts = $contactRepo->getRecentPosts($author, 4);
        $this->assertCount(4, $posts);
    }
}
