<?php

namespace App\Entity;

use App\Repository\RedirectRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RedirectRepository::class)
 */
class Redirect
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $path;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $destination;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        // Must always start with a slash.
        $this->path = '/' . ltrim($path, '/');
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): void
    {
        $this->destination = $destination;
    }

    public function isRedirect(): bool
    {
        return 300 <= $this->getStatus() && $this->getStatus() < 400;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }
}
