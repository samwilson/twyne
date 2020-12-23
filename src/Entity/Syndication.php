<?php

namespace App\Entity;

use App\Repository\SyndicationRepository;
use Doctrine\ORM\Mapping as ORM;
use Samwilson\PhpFlickr\Util;

/**
 * @ORM\Table(
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="syndication_unique", columns={"post_id", "url"})
 *    }
 * )
 * @ORM\Entity(repositoryClass=SyndicationRepository::class)
 */
class Syndication
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Post::class, inversedBy="syndications")
     * @ORM\JoinColumn(nullable=false)
     */
    private $post;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): void
    {
        $this->post = $post;
        $post->addSyndication($this);
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        // Make sure URLs start with the protocol.
        if (substr($url, 0, 4) !== 'http') {
            $url = "https://$url";
        }
        $this->url = $url;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
