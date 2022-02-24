<?php

namespace App;

use App\Entity\File;
use App\Entity\Post;
use DateTime;
use DOMDocument;
use DOMElement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Rss
{
    /** @var Settings */
    private $settings;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var Markdown */
    private $markdown;

    /** @var DOMDocument */
    private $dom;

    public function __construct(Settings $settings, UrlGeneratorInterface $urlGenerator, Markdown $markdown)
    {
        $this->settings = $settings;
        $this->urlGenerator = $urlGenerator;
        $this->markdown = $markdown;
    }

    /**
     * @param Post[] $posts
     * @param string $title Optional subtitle of the feed.
     */
    public function get(array $posts, string $title = null): string
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');

        $root = $this->dom->createElement('rss');
        $root->setAttribute('version', '2.0');
        $root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
        $root->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $root = $this->dom->appendChild($root);

        $channel = $this->dom->createElement('channel');
        $channel = $root->appendChild($channel);

        $title = $this->dom->createElement('title', $this->settings->siteName() . ($title ? ' :: ' . $title : ''));
        $channel->appendChild($title);

        $homeUrl = $this->urlGenerator->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $channel->appendChild($this->dom->createElement('link', $homeUrl));

        $selfUrl = $this->urlGenerator->generate('rss', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $atomLink = $this->dom->createElement('atom:link');
        $atomLink->setAttribute('href', $selfUrl);
        $atomLink->setAttribute('rel', 'self');
        $atomLink->setAttribute('type', 'application/rss+xml');
        $channel->appendChild($atomLink);

        $date = new DateTime();
        $lastBuildDate = $this->dom->createElement('lastBuildDate', $date->format(DateTime::RSS));

        $channel->appendChild($lastBuildDate);

        foreach ($posts as $post) {
            $channel->appendChild($this->getItem($post));
        }
        return $this->dom->saveXML();
    }

    public function getItem(Post $post): DOMElement
    {
        $item = $this->dom->createElement('item');

        $title = $this->dom->createElement('title', $post->getTitle() ?: 'Post ' . $post->getId());
        $item->appendChild($title);

        if ($post->getBody()) {
            $body = $this->markdown->toHtml($post->getBody());
            $description = $this->dom->createElement('description', $body);
            $item->appendChild($description);
        }

        $url = $post->getUrl();
        if (!$url) {
            $url = $this->urlGenerator->generate(
                'post_view',
                ['id' => $post->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }
        $item->appendChild($this->dom->createElement('link', $url));
        $guid = $this->dom->createElement('guid', $url);
        $guid->setAttribute('isPermaLink', 'true');
        $item->appendChild($guid);

        $date = $post->getDate()->format(DateTime::RSS);
        $item->appendChild($this->dom->createElement('pubDate', $date));

        $file = $post->getFile();
        if ($file) {
            $mediaContent = $this->dom->createElement('media:content');
            $url = $this->urlGenerator->generate(
                'file',
                ['id' => $post->getId(), 'size' => File::SIZE_DISPLAY, 'ext' => 'jpg'],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $mediaContent->setAttribute('isDefault', 'true');
            $mediaContent->setAttribute('url', $url);
            $mediaContent->setAttribute('medium', 'image');
            $mediaContent->setAttribute('type', 'image/jpeg');
            $item->appendChild($mediaContent);
        }

        return $item;
    }
}
