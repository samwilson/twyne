<?php

namespace Samwilson\Twyne\Controller;

use Samwilson\Twyne\Data\FeedItem;
use Samwilson\Twyne\Data\Post;

class HomeController extends ControllerBase
{

    public function homeGet()
    {
        $tpl = $this->getTemplate('home.html');
        $tpl->stylesheet = 'home';
        $tpl->alerts = $this->session->getAndDelete('alerts');
        $tpl->posts = Post::getRecent(10);
        $tpl->feed_items = FeedItem::getRecent(10);
        $this->outputTemplate($tpl);
    }
}
