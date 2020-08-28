<?php

namespace Samwilson\Twyne\Data;

use Exception;
use Samwilson\Twyne\Database;

class Setting extends ModelBase
{

    /** @var */
    private static $allData;

    public static function getTableName(): string
    {
        return 'settings';
    }

    public static function getTableAbbr(): string
    {
        return 's';
    }

    private static function getData()
    {
        if (static::$allData) {
            return static::$allData;
        }
        static::$allData = Database::getInstance()->query('SELECT * FROM `settings`')->fetchAll();
        return static::$allData;
    }

    public static function loadById($id): Setting
    {
        foreach (static::getData() as $datum) {
            if ($datum->s_id === $id) {
                return new static((object)$datum);
            }
        }
    }

    public static function loadByName(string $name): Setting
    {
        $datum = false;
        try {
            foreach (static::getData() as $d) {
                if ($d->s_name === $name) {
                    $datum = $d;
                }
            }
        } catch (Exception $exception) {
            // Don't worry if the DB fails, this may be called during installation.
        }
        if (!$datum) {
            $datum = ['s_name' => $name];
        }
        return new self((object)$datum);
    }

    public function getValue($default = null)
    {
        return isset($this->data->s_value) ? unserialize($this->data->s_value) : $default;
    }

    public function setValue(string $value)
    {
        $this->data->s_value = serialize($value);
    }
}
