<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\Post;
use App\Entity\UserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FileExistsException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * @method File|null find($id, $lockMode = null, $lockVersion = null)
 * @method File|null findOneBy(array $criteria, array $orderBy = null)
 * @method File[]    findAll()
 * @method File[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    /**
     * Check an uplodaed file to confirm it's of the right type and doesn't already exist.
     */
    public function checkFile(UploadedFile $file): bool
    {
        if (!in_array($file->guessExtension(), ['png', 'pdf', 'jpg', 'jpeg', 'gif'])) {
            return false;
        }
        $existing = $this->findBy(['checksum' => sha1_file($file->getPathname())]);
        if ($existing) {
            return false;
        }
        return true;
    }

    /**
     * @param array $checksums List of sha1 checksums to look for.
     * @param bool $publicOnly Whether to only return publicly-readable posts.
     * @return int|mixed|string The files.
     */
    public function findByChecksums(array $checksums, bool $publicOnly = true)
    {
        $query = $this->createQueryBuilder('f')
            ->where('f.checksum IN (:checksums)')
            ->setParameter('checksums', $checksums);
        if ($publicOnly) {
            $query
                ->join(Post::class, 'p', Join::WITH, 'f.post = p.id')
                ->andWhere('p.view_group = ' . UserGroup::PUBLIC);
        }
        return $query
            ->getQuery()
            ->getResult();
    }
}
