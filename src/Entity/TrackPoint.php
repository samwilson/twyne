<?php

namespace App\Entity;

use App\Repository\TrackPointRepository;
use CrEOF\Spatial\PHP\Types\Geometry\Point;
use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;

/**
 * @ORM\Entity(repositoryClass=TrackPointRepository::class)
 */
class TrackPoint
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="point")
     */
    private $location;

    /**
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocation(): Point
    {
        return $this->location;
    }

    public function setLocation(Point $location): void
    {
        $this->location = $location;
    }

    public function getTimestamp(): DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(DateTimeInterface $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
