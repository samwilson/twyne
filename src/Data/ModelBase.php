<?php

namespace Samwilson\Twyne\Data;

use Exception;
use Samwilson\Twyne\Database;
use stdClass;

/**
 * The ModelBase class is inherited by all model classes. It provides basic CRUD functionality. Every subclass should
 * have whatever getters and setters it needs, separate methods for loading and saving.
 */
abstract class ModelBase
{

    /** @var Database The database object (for convenience only). */
    protected $db;

    /** @var stdClass A single row's data. */
    protected $data;

    public function __construct(stdClass $data = null)
    {
        $this->data = $data ?? new stdClass();
        $this->db = Database::getInstance();
    }

    /**
     * @deprecated
     * @return Database
     */
    protected static function getDb()
    {
        return Database::getInstance();
    }

    abstract public static function getTableName(): string;

    abstract public static function getTableAbbr(): string;

    public function exists(): bool
    {
        return $this->getId() !== null;
    }

    public function getId(): ?int
    {
        $idCol = static::getTableAbbr() . '_id';
        return isset($this->data->$idCol) && $this->data->$idCol ? $this->data->$idCol : null;
    }

    public static function loadById($id)
    {
        $model = new static();
        $model->reloadById($id);
        return $model;
    }

    public function reloadById($id)
    {
        if (!is_numeric($id)) {
            throw new Exception("Non-numberic ID: \$id");
        }
        $sql = 'SELECT * FROM `' . static::getTableName() . '` WHERE `' . static::getTableAbbr() . '_id` = :id';
        $this->data = Database::getInstance()->query($sql, ['id' => $id])->fetch();
    }

    public function save()
    {
        $params = [];
        $setPairs = [];
        $idColName = static::getTableAbbr() . '_id';
        foreach ($this->data as $colName => $value) {
            $params[':' . $colName] = $value;
            $setPairs[$colName] = '`' . $colName . '` = :' . $colName;
        }
        if ($this->getId()) {
            unset($setPairs[$idColName]);
            $sql = 'UPDATE `' . static::getTableName() . '` SET ' . join(', ', $setPairs)
                . ' WHERE `' . $idColName . '` = :' . $idColName;
        } else {
            $sql = 'INSERT INTO `' . static::getTableName() . '` SET ' . join(', ', $setPairs);
        }
        $this->db->query($sql, $params);
        if (!$this->getId()) {
            $this->reloadById($this->db->lastInsertId());
        }
    }

    public static function getAll()
    {
        return Database::getInstance()->query('SELECT * FROM ' . static::getTableName())->fetchAll();
    }
}
