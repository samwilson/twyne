<?php

namespace Samwilson\Twyne;

use Exception;
use PDO;
use PDOException;
use PDOStatement;
use Samwilson\Twyne\Data\Setting;

class Database
{

    /** @var PDO */
    protected static $pdo;

    /** @var string[] */
    protected static $queries = [];

    /** @var Database */
    protected static $instance;

    public static function getInstance(): Database
    {
        if (!self::$instance instanceof Database) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        if (self::$pdo instanceof PDO) {
            // Already configured.
            return;
        }
        $config = new Config();
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8', $config->databaseHost(), $config->databaseName());
        $attr = array(PDO::ATTR_TIMEOUT => 10);
        self::$pdo = new PDO($dsn, $config->databaseUser(), $config->databasePassword(), $attr);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setFetchMode(PDO::FETCH_OBJ);
    }

    public static function getQueries()
    {
        return self::$queries;
    }

    /**
     * Wrapper for \PDO::lastInsertId().
     * @return string
     */
    public function lastInsertId()
    {
        return self::$pdo->lastInsertId();
    }

    public function setFetchMode($fetchMode)
    {
        return self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $fetchMode);
    }

    /**
     * Get a result statement for a given query. Handles errors.
     *
     * @param string $sql The SQL statement to execute.
     * @param array $params Array of param => value pairs.
     * @param string $class The PHP class of each item of the result set.
     * @param mixed $classArgs The arguments to pass to the constructor of the class.
     * @return PDOStatement Resulting PDOStatement.
     * @throws Exception If the requested result class does not exist.
     */
    public function query($sql, $params = null, $class = null, $classArgs = null)
    {
        if (!empty($class) && !class_exists($class)) {
            throw new Exception("Class not found: $class");
        }
        if (is_array($params) && count($params) > 0) {
            $stmt = self::$pdo->prepare($sql);
            foreach ($params as $placeholder => $value) {
                if (is_bool($value)) {
                    $type = PDO::PARAM_BOOL;
                } elseif (is_null($value)) {
                    $type = PDO::PARAM_NULL;
                } elseif (is_int($value)) {
                    $type = PDO::PARAM_INT;
                } else {
                    $type = PDO::PARAM_STR;
                }
                $stmt->bindValue($placeholder, $value, $type);
            }
            if ($class !== null) {
                $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class, $classArgs);
            } else {
                $stmt->setFetchMode(PDO::FETCH_OBJ);
            }
            $result = $stmt->execute();
            if (!$result) {
                throw new PDOException('Unable to execute parameterised SQL: <code>' . $sql . '</code>');
            }
        } else {
            try {
                if ($class !== null) {
                    $stmt = self::$pdo->query($sql, PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class, $classArgs);
                } else {
                    $stmt = self::$pdo->query($sql);
                }
            } catch (PDOException $e) {
                throw new PDOException($e->getMessage() . ' -- Unable to execute SQL: <code>' . $sql . '</code>');
            }
        }

        self::$queries[] = $sql;
        return $stmt;
    }

    public function install()
    {
        $dbVersion = Setting::loadByName('db_version');
        $nextVersion = $dbVersion->getValue(0) + 1;
        $upgradeMethod = "upgrade" . $nextVersion;
        while (method_exists($this, $upgradeMethod)) {
            // Run this upgrade version.
            $this->$upgradeMethod();
            // Save new current version.
            $dbVersion->setValue($nextVersion);
            $dbVersion->save();
            // Increment to next version.
            $upgradeMethod = "upgrade" . $nextVersion;
            $nextVersion++;
        }
    }

    private function upgrade1()
    {
        // All column names should be unique.
        $this->query("CREATE TABLE IF NOT EXISTS `users` ("
            . " `u_id` INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
            . " `u_username` VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL UNIQUE,"
            . " `u_email` VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL UNIQUE,"
            . " `u_password` VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL,"
            . " `u_reminder_token` VARCHAR(191) CHARACTER SET utf8mb4 NULL DEFAULT NULL,"
            . " `u_reminder_time` DATETIME NULL DEFAULT NULL,"
            . " `u_contact` INT(5) UNSIGNED NULL DEFAULT NULL"
            . " ) DEFAULT CHARSET=utf8mb4");
        $this->query("CREATE TABLE IF NOT EXISTS `contacts` ("
            . " `c_id` INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
            . ' `c_user` INT(5) UNSIGNED NOT NULL,'
            . ' FOREIGN KEY `fk_contact_user` (`c_user`) REFERENCES `users` (`u_id`),'
            . " `c_name` VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL,"
            . ' `c_description` VARCHAR(300) CHARACTER SET utf8mb4 NULL DEFAULT NULL,'
            . " UNIQUE KEY (`c_user`, `c_name`)"
            . " ) DEFAULT CHARSET=utf8mb4");
        $this->query('ALTER TABLE `users`'
            . ' ADD FOREIGN KEY `fk_user_contact` (`u_contact`) REFERENCES `contacts` (`c_id`)');
        $this->query('CREATE TABLE IF NOT EXISTS `posts` ('
            . ' `p_id` INT(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,'
            . ' `p_user` INT(5) UNSIGNED NOT NULL,'
            . ' FOREIGN KEY `fk_post_user` (`p_user`) REFERENCES `users` (`u_id`),'
            . ' `p_author` INT(5) UNSIGNED NOT NULL,'
            . ' FOREIGN KEY `fk_post_author` (`p_author`) REFERENCES `contacts` (`c_id`),'
            . ' `p_datetime` DATETIME NOT NULL,'
            . ' `p_original_url` TEXT CHARACTER SET utf8mb4 NULL DEFAULT NULL,'
            . ' `p_description` VARCHAR(300) CHARACTER SET utf8mb4 NULL DEFAULT NULL,'
            . ' `p_body` TEXT CHARACTER SET utf8mb4 NOT NULL'
            . ' ) DEFAULT CHARSET=utf8mb4;');
        $this->query('CREATE TABLE IF NOT EXISTS `feeds` ('
            . ' `f_id` INT(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,'
            . ' `f_contact` INT(5) UNSIGNED NOT NULL,'
            . ' FOREIGN KEY `fk_feed_contact` (`f_contact`) REFERENCES `contacts` (`c_id`),'
            . ' `f_type` VARCHAR(30) NOT NULL DEFAULT "rss",'
            . ' `f_value` VARCHAR(300) NOT NULL'
            . ') DEFAULT CHARSET=utf8mb4;');
        $this->query('CREATE TABLE IF NOT EXISTS `feed_items` ('
            . ' `fi_id` INT(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,'
            . ' `fi_feed` INT(5) UNSIGNED NOT NULL,'
            . ' FOREIGN KEY `fk_feed_item_feed` (`fi_feed`) REFERENCES `feeds` (`f_id`),'
            . ' `fi_original_url` TEXT CHARACTER SET utf8mb4 NULL DEFAULT NULL,'
            . ' `fi_title` VARCHAR(300) CHARACTER SET utf8mb4 NULL DEFAULT NULL,'
            . ' `fi_datetime` DATETIME NOT NULL,'
            . ' `fi_body` TEXT CHARACTER SET utf8mb4 NOT NULL'
            . ') DEFAULT CHARSET=utf8mb4;');
        $this->query("CREATE TABLE IF NOT EXISTS `settings` ("
            . " `s_id` INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
            . ' `s_name` VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL UNIQUE,'
            . " `s_value` VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL"
            . " ) DEFAULT CHARSET=utf8mb4");
    }

    private function upgrade2()
    {
        $this->query("ALTER TABLE `users`"
            . " CHANGE `u_username` `u_username` VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL UNIQUE,"
            . " CHANGE `u_email` `u_email` VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL UNIQUE,"
            . " CHANGE `u_password` `u_password` VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL,"
            . " CHANGE `u_reminder_token` `u_reminder_token` VARCHAR(191) CHARACTER SET utf8mb4 NULL DEFAULT NULL"
            . ";");
    }
}
