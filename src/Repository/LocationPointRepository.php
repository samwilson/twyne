<?php

namespace App\Repository;

use App\Entity\LocationPoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Entity\UserGroup;

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

    public function findByBoundingBox(string $neLat, string $neLng, string $swLat, string $swLng)
    {
        $ne = "$neLng $neLat";
        $se = "$neLng $swLat";
        $sw = "$swLng $swLat";
        $nw = "$swLng $neLat";
        // Note start and end points are the same.
        $wkt = "Polygon(($ne, $se, $sw, $nw, $ne))";
        $sql = "SELECT ST_X(location) AS lng, ST_Y(location) AS lat, timestamp"
            . " FROM location_point"
            . " WHERE ST_Contains(GeomFromText(:wkt), location)"
            . " ORDER BY RAND()"
            . " LIMIT 500";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindParam('wkt', $wkt);
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}
