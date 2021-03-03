<?php

namespace App\Repository;

use App\Entity\LocationPoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LocationPoint|null find($id, $lockMode = null, $lockVersion = null)
 * @method LocationPoint|null findOneBy(array $criteria, array $orderBy = null)
 * @method LocationPoint[]    findAll()
 * @method LocationPoint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocationPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LocationPoint::class);
    }
}
