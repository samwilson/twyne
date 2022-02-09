<?php

namespace App\Repository;

use App\Entity\TrackPoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TrackPoint|null find($id, $lockMode = null, $lockVersion = null)
 * @method TrackPoint|null findOneBy(array $criteria, array $orderBy = null)
 * @method TrackPoint[]    findAll()
 * @method TrackPoint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrackPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackPoint::class);
    }

    public function findByBoundingBox(string $neLat, string $neLng, string $swLat, string $swLng)
    {
        $ne = "$neLng $neLat";
        $se = "$neLng $swLat";
        $sw = "$swLng $swLat";
        $nw = "$swLng $neLat";
        // Note start and end points are the same.
        $wkt = "Polygon(($ne, $se, $sw, $nw, $ne))";
        $sql = "SELECT ST_X(location) AS lng, ST_Y(location) AS lat"
            . " FROM location_point"
            . " WHERE ST_Contains(GeomFromText(:wkt), location)"
            . " ORDER BY RAND()"
            . " LIMIT 500";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindParam('wkt', $wkt);
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}
