<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ContactRepository::class)
 */
class Contact
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description_public;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description_private;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $homepage;

    /**
     * @ORM\OneToMany(targetEntity=Post::class, mappedBy="author")
     */
    private $posts;

    /**
     * @ORM\OneToOne(targetEntity=User::class, mappedBy="contact", cascade={"persist", "remove"})
     */
    private $user;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescriptionPublic(): ?string
    {
        return $this->description_public;
    }

    public function setDescriptionPublic(?string $description_public): self
    {
        $this->description_public = $description_public;

        return $this;
    }

    public function getDescriptionPrivate(): ?string
    {
        return $this->description_private;
    }

    public function setDescriptionPrivate(?string $description_private): self
    {
        $this->description_private = $description_private;

        return $this;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setHomepage(?string $homepage): self
    {
        if ($homepage && substr($homepage, 0, 4) !== 'http') {
            $homepage = "https://$homepage";
        }
        $this->homepage = $homepage;

        return $this;
    }

    /**
     * @return Collection|Post[]
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            // set the owning side to null (unless already changed)
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        // set the owning side of the relation if necessary
        if ($user->getContact() !== $this) {
            $user->setContact($this);
        }

        return $this;
    }
}
