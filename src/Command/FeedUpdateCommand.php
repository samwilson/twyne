<?php

namespace Samwilson\Twyne\Command;

use Samwilson\Twyne\Config;
use Samwilson\Twyne\Data\Feed;
use Samwilson\Twyne\Data\FeedItem;
use SimplePie;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FeedUpdateCommand extends Command
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('feeds');
        $this->setDescription('Fetch updates for all feeds.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int Null or 0 if everything went fine, or an error code.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $feeds = Feed::getAll();
        $simplepie = new SimplePie();
        $tmpDir = (new Config())->tmpDir();
        $simplepie->set_cache_location($tmpDir . 'feeds');
        foreach ($feeds as $feed) {
            $simplepie->set_feed_url($feed->f_value);
        }
        $simplepie->init();
        foreach ($simplepie->get_items() as $item) {
            $body = $item->get_content() ?? $item->get_description() ?? $item->get_title();
            $feedItem = new FeedItem();
            $feedItem->setFeed($feed->f_id);
            $feedItem->setTitle($item->get_title());
            $feedItem->setBody($body);
            $feedItem->setOriginalUrl($item->get_link());
            $feedItem->setDatetime($item->get_gmdate());
            $feedItem->save();
        }
    }
}
