<?php

namespace App\Entity;

use App\Repository\PostRepository;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PostRepository::class)
 */
class Post
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $body;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity=Contact::class, inversedBy="posts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @ORM\OneToOne(targetEntity=File::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $file;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $url;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class, inversedBy="posts")
     */
    private $tags;

    public function __construct()
    {
        $this->setDate(new DateTime('now', new DateTimeZone('Z')));
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getDate(): ?DateTimeInterface
    {
        return new DateTime($this->date->format('Y-m-d H:i:s'), new DateTimeZone('Z'));
    }

    public function setDate(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getAuthor(): ?Contact
    {
        return $this->author;
    }

    public function setAuthor(Contact $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;
        if ($file && $file->getPost() !== $this) {
            $file->setPost($this);
        }
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function getTagsString(): string
    {
        $out = [];
        foreach ($this->getTags() as $tag) {
            $out[] = $tag->getTitle();
        }
        return implode('; ', $out);
    }

    /**
     * @param Collection|Tag[] $tags
     */
    public function setTags(Collection $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }
        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }
        return $this;
    }
}
