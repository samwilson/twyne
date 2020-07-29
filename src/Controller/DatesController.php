<?php

namespace Samwilson\Twyne\Controller;

use Samwilson\Twyne\Data\Post;

class DatesController extends ControllerBase
{
    public function viewGet($args)
    {
        $tpl = $this->getTemplate('dates.html');
        $tpl->stylesheet = 'dates';
        $date = $args['date'] ?? false;
        $tpl->dates = Post::getDates($date);
        $tpl->posts = Post::getByDate($date);
        $this->outputTemplate($tpl);
    }
}
