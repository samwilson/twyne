<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Syndication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Syndication|null find($id, $lockMode = null, $lockVersion = null)
 * @method Syndication|null findOneBy(array $criteria, array $orderBy = null)
 * @method Syndication[]    findAll()
 * @method Syndication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SyndicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Syndication::class);
    }

    public function addSyndication(Post $post, string $url, string $label): void
    {
        $syndication = new Syndication();
        $syndication->setPost($post);
        $syndication->setUrl($url);
        $syndication->setLabel($label);
        $this->getEntityManager()->persist($syndication);
        $this->getEntityManager()->flush();
    }
}
