<?php

namespace App\Tests\Repository;

use App\Entity\Contact;
use App\Entity\File;
use App\Entity\Post;
use App\Repository\FileRepository;
use App\Repository\PostRepository;

class FileRepositoryTest extends RepositoryTestBase
{

    public function testFindByChecksums()
    {
        /** @var FileRepository $fileRepo */
        $fileRepo = $this->entityManager->getRepository(File::class);
        /** @var PostRepository $fileRepo */
        $postRepo = $this->entityManager->getRepository(Post::class);
        /** @var PostRepository $fileRepo */
        $contactRepo = $this->entityManager->getRepository(Contact::class);

        // Nothing.
        $result1 = $fileRepo->findByChecksums([]);
        $this->assertSame([], $result1);

        // Still nothing.
        $result2 = $fileRepo->findByChecksums(['foobar']);
        $this->assertSame([], $result2);

        // Add a file.
        $post = $postRepo->createNew();
        $post->setAuthor($contactRepo->getOrCreate('Test author'));
        $filename = dirname(__DIR__) . '/data/no-metadata.jpg';
        $fileRepo->saveFile($post, $filename, 'image/jpeg', filesize($filename));

        // Check again for invalid checksum and the checksum of no-metadata.jpg
        $result3 = $fileRepo->findByChecksums(['foobar']);
        $this->assertSame([], $result3);
        $result3 = $fileRepo->findByChecksums(['a8267f2c78fceab326b3e3ee8ea720b6cadb2196', 'lorem']);
        $this->assertSame($post, $result3[0]->getPost());
    }
}
