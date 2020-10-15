<?php

namespace App\Entity;

use App\Repository\FileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Mime\MimeTypes;

/**
 * @ORM\Entity(repositoryClass=FileRepository::class)
 */
class File
{

    /** @var string */
    public const SIZE_FULL = 'F';

    /** @var string */
    public const SIZE_DISPLAY = 'D';

    /** @var string */
    public const SIZE_THUMB = 'T';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Post::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $post;

    /**
     * @ORM\Column(type="integer")
     */
    private $size;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $mimeType;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $checksum;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get the file extension.
     *
     * @return string|null
     */
    public function getExtension(): ?string
    {
        if (!$this->getMimeType()) {
            return null;
        }
        $ext = MimeTypes::getDefault()->getExtensions($this->getMimeType())[0] ?? null;
        return $ext === 'jpeg' ? 'jpg' : $ext;
    }

    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }
}
