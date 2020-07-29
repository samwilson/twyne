<?php

namespace Samwilson\Twyne\Data;

class Feed extends ModelBase
{

    /** @var Contact */
    private $contact;

    /** @var string */
    private $type;

    public static function getTableName(): string
    {
        return 'feeds';
    }

    public static function getTableAbbr(): string
    {
        return 'f';
    }

    public function setContact(Contact $contact)
    {
        $this->contact = $contact;
        $this->data->f_contact = $contact->getId();
    }

    public function setType(string $type)
    {
        $this->data->f_type = $type;
    }

    public function setValue(string $value)
    {
        $this->data->f_value = $value;
    }

    /**
     * @return FeedItem[]
     */
    public function getItems()
    {
        $data = $this->db->query(
            'SELECT * FROM feed_items WHERE fi_feed = :fid',
            ['fid' => $this->getId()]
        );
        $out = [];
        foreach ($data as $datum) {
            $out[] = new FeedItem($datum);
        }
        return $out;
    }
}
