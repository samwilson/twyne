<?php

namespace Samwilson\Twyne\Controller;

use Less_Parser;

class AssetsController extends ControllerBase
{

    public function cssGet($args): void
    {
        // @todo Add caching.
        $parser = new Less_Parser(['compress' => true]);
        $parser->parseFile(dirname(__DIR__, 2) . '/resources/' . $args['file'] . '.less');
        header('Content-Type:text/css');
        echo $parser->getCss();
    }
}
