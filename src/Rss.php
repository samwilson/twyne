<?php

namespace App;

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

    /** @var DOMDocument */
    private $dom;

    public function __construct(Settings $settings, UrlGeneratorInterface $urlGenerator)
    {
        $this->settings = $settings;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param Post[] $posts
     */
    public function get(array $posts): string
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');

        $root = $this->dom->createElement('rss');
        $root->setAttribute('version', '2.0');
        $root = $this->dom->appendChild($root);

        $channel = $this->dom->createElement('channel');
        $channel = $root->appendChild($channel);

        $title = $this->dom->createElement('title', $this->settings->siteName());
        $channel->appendChild($title);

        $homeUrl = $this->urlGenerator->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $channel->appendChild($this->dom->createElement('link', $homeUrl));

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

        $title = $this->dom->createElement('title', $post->getTitle());
        $item->appendChild($title);

        $description = $this->dom->createElement('description', $post->getBody());
        $item->appendChild($description);

        $url = $this->urlGenerator->generate(
            'post_view',
            ['id' => $post->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $item->appendChild($this->dom->createElement('link', $url));

        $date = $post->getDate()->format(DateTime::RSS);
        $item->appendChild($this->dom->createElement('pubDate', $date));

        return $item;
//         <item>
//  <title>Example entry</title>
//  <description>Here is some text containing an interesting description.</description>
//  <link>http://www.example.com/blog/post/1</link>
//  <guid isPermaLink="false">7bd204c6-1655-4c27-aeee-53f933c5395f</guid>
//  <pubDate>Sun, 06 Sep 2009 16:20:00 +0000</pubDate>
// </item>
    }
}
