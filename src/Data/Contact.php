<?php

namespace Samwilson\Twyne\Data;

use Exception;
use Samwilson\Twyne\Database;

class Contact extends ModelBase
{

    public static function getTableName(): string
    {
        return 'contacts';
    }

    public static function getTableAbbr(): string
    {
        return 'c';
    }

    public static function getByUserAndName(User $user, string $name)
    {
        $sql = 'SELECT * FROM contacts WHERE c_user = :user AND c_name = :name';
        $contactData = Database::getInstance()->query($sql, ['user' => $user->getId(), 'name' => $name])->fetch();
        if (!$contactData) {
            throw new Exception('Unable to find contact "' . $name . '"');
        }
        return new static($contactData);
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->data->c_name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->data->c_name = trim($name);
    }

    public function setUser(User $user)
    {
        $this->data->c_user = $user->getId();
    }

    public function getFeeds()
    {
        return Database::getInstance()->query('SELECT * FROM feeds WHERE f_contact = :cid', ['cid' => $this->getId()]);
    }

    public static function getAllWithFeeds()
    {
        return Database::getInstance()->query('SELECT * FROM contacts JOIN feeds ON c_id=f_contact')->fetchAll();
    }
}
