<?php

namespace Samwilson\Twyne\Data;

use Samwilson\Twyne\Database;

class FeedItem extends ModelBase
{

    public static function getTableName(): string
    {
        return 'feed_items';
    }

    public static function getTableAbbr(): string
    {
        return 'fi';
    }


    public function setFeed($feed)
    {
        $this->data->fi_feed = $feed;
    }

    public function setOriginalUrl($originalUrl)
    {
        $this->data->fi_original_url = $originalUrl;
    }

    public function setTitle($title)
    {
        $this->data->fi_title = $title;
    }

    public function setDatetime(string $datetime)
    {
        $this->data->fi_datetime = date('Y-m-d H:i:s', strtotime($datetime));
    }

    public function setBody($body)
    {
        $this->data->fi_body = $body;
    }

    public static function getRecent($limit = 10)
    {
        return Database::getInstance()->query(
            'SELECT * FROM `feed_items` 
            JOIN feeds ON fi_feed = f_id
            JOIN contacts ON f_contact = c_id
            ORDER BY `fi_datetime` DESC LIMIT :limit',
            ['limit' => $limit]
        );
    }
}
