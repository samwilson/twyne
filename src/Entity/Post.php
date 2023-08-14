<?php

namespace App\Entity;

use App\Repository\PostRepository;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;

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

    /**
     * @ORM\Column(type="point", nullable=true)
     */
    private $location;

    /**
     * @ORM\ManyToOne(targetEntity=Post::class, inversedBy="replies")
     */
    private $in_reply_to;

    /**
     * @ORM\OneToMany(targetEntity=Post::class, mappedBy="in_reply_to")
     */
    private $replies;

    /**
     * @ORM\OneToMany(targetEntity=Syndication::class, mappedBy="post")
     */
    private $syndications;

    /**
     * @ORM\ManyToOne(targetEntity=UserGroup::class, inversedBy="posts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $view_group;

    public function __construct()
    {
        $this->setDate(new DateTime('@' . time(), new DateTimeZone('Z')));
        $this->tags = new ArrayCollection();
        $this->replies = new ArrayCollection();
        $this->syndications = new ArrayCollection();
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

    public function getLocation(): ?Point
    {
        return $this->location;
    }

    public function setLocation(?Point $location = null): self
    {
        $this->location = $location;

        return $this;
    }

    public function getInReplyTo(): ?self
    {
        return $this->in_reply_to;
    }

    public function setInReplyTo(?self $in_reply_to): self
    {
        $this->in_reply_to = $in_reply_to;
        if (!$this->getId()) {
            // For new posts, set the view group to match the parent post.
            $this->setViewGroup($in_reply_to->getViewGroup());
        }

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    public function addReply(self $reply): self
    {
        if (!$this->replies->contains($reply)) {
            $this->replies[] = $reply;
            $reply->setInReplyTo($this);
        }

        return $this;
    }

    public function removeReply(self $reply): self
    {
        if ($this->replies->removeElement($reply)) {
            // set the owning side to null (unless already changed)
            if ($reply->getInReplyTo() === $this) {
                $reply->setInReplyTo(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Syndication[]
     */
    public function getSyndications(): Collection
    {
        return $this->syndications;
    }

    /**
     * @param Collection|Syndication[] $syndications
     */
    public function setSyndications(Collection $syndications): void
    {
        $this->syndications = $syndications;
    }

    public function addSyndication(Syndication $syndication): void
    {
        if (!$this->syndications->contains($syndication)) {
            $this->syndications[] = $syndication;
            $syndication->setPost($this);
        }
    }

    public function removeSyndication(Syndication $syndication): void
    {
        if ($this->syndications->removeElement($syndication)) {
            // set the owning side to null (unless already changed)
            if ($syndication->getPost() === $this) {
                $syndication->setPost(null);
            }
        }
    }

    public function getViewGroup(): ?UserGroup
    {
        return $this->view_group;
    }

    public function setViewGroup(?UserGroup $view_group): self
    {
        $this->view_group = $view_group;

        return $this;
    }

    /**
     * Can this post be viewed by the given user?
     * @param User|null $user
     * @return bool
     */
    public function canBeViewedBy(?User $user = null): bool
    {
        if ($this->getViewGroup()->getId() === UserGroup::PUBLIC) {
            return true;
        }
        return $user && $user->isInGroup($this->getViewGroup());
    }
}
