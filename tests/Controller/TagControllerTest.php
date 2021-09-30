<?php

namespace App\Tests\Controller;

use App\Entity\Post;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

class TagControllerTest extends ControllerTestBase
{

    public function testTagPage()
    {
        $client = static::createClient();
        $this->createAccountAndLogIn($client);

        // Create a new public post, with a tag.
        $client->request('GET', '/post/new');
        $crawler = $client->getCrawler();
        $buttonNode = $crawler->selectButton('Save');
        $form = new Form($buttonNode->getNode(0), $crawler->getUri(), 'POST', $crawler->getBaseHref());
        // Disable validation, because the 'tag1' option doesn't exist in the HTML (it's added by JS).
        $form->disableValidation();
        $form->setValues(['tags' => ['tag1']]);
        $client->submit($form);
        $client->followRedirect();

        // Now we're on the new post's page, go to the tag page.
        $postUri = $client->getCrawler()->getUri();
        $client->clickLink('tag1');
        $this->assertSelectorTextContains('main h1', 'tag1');
        $tagUri = $client->getCrawler()->getUri();

        // Confirm that the post is listed.
        $postLink = substr($postUri, strrpos($postUri, '/'));
        $this->assertSelectorExists('a[href="' . $postLink . '"]');

        // Edit the post to make it private.
        $client->click($client->getCrawler()->filter('ol.post-list')->selectLink('Edit')->link());
        $secondGroupId = $client->getCrawler()
            ->filter('#view_group option')
            ->getNode(1)
            ->attributes
            ->getNamedItem('value')
            ->nodeValue;
        $client->submitForm('Save', ['view_group' => $secondGroupId], 'POST');
        $client->followRedirect();

        // Log out, go back to the tag page, and find no posts.
        $client->clickLink('Log out');
        $client->request('GET', $tagUri);
        $this->assertSelectorTextContains('main h1', 'tag1');
    }
}
