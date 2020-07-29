<?php

namespace Samwilson\Twyne\Data;

use DateInterval;
use DateTime;
use DateTimeZone;
use Samwilson\Twyne\Database;
use stdClass;

class User extends ModelBase
{

    public static function getTableName(): string
    {
        return 'users';
    }

    public static function getTableAbbr(): string
    {
        return 'u';
    }

    /**
     * Register a new user.
     * @param string $email
     * @param string $password
     * @return User|bool The registered user, or false if it could not be registered.
     */
    public static function register($username, $email, $password)
    {
        $user = static::loadByEmailOrUserame($email, $username);
        if ($user->exists()) {
            return false;
        }

        $db = Database::getInstance();
        $userParams = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email' => $email,
            'username' => $username,
        ];
        $userSql = "INSERT INTO `users` SET `u_password` = :password, `u_email` = :email, `u_username` = :username";
        $db->query($userSql, $userParams);
        $userId = $db->lastInsertId();

        // Create a contact.
        $contactParams = ['name' => $username, 'user' => $userId];
        $db->query("INSERT INTO `contacts` SET `c_name` = :name, `c_user` = :user", $contactParams);
        $contactId = $db->lastInsertId();

        // Then update the User with the Contact ID.
        $sql = "UPDATE `users` SET `u_contact` = :contact WHERE `u_id` = :id";
        $db->query($sql, ['contact' => $contactId, 'id' => $userId]);

        return self::loadById($userId);
    }

    public function getUsername(): string
    {
        return $this->data->u_username;
    }

    /**
     * Get a new reminder token.
     * @return bool|string The reminder token, or false if the user isn't loaded yet.
     */
    public function getReminderToken()
    {
        if (!$this->exists()) {
            return false;
        }
        $reminderTime = new DateTime();
        $reminderTime->setTimezone(new DateTimeZone('Z'));
        $sql = "UPDATE `users` SET `u_reminder_token` = :tok, `u_reminder_time` = :now WHERE `u_id` = :id";
        // @todo What should this be?
        $unhashedToken = md5($this->getUsername() . time());
        $params = [
            'tok' => password_hash($unhashedToken, PASSWORD_DEFAULT),
            'id' => $this->getId(),
            'now' => $reminderTime->format('Y-m-d H:i:s'),
        ];
        $this->db->query($sql, $params)->execute();
        $this->reloadById($this->getId());
        return $unhashedToken;
    }

    public function checkReminderToken(string $token): bool
    {
        $matches = password_verify($token, $this->data->u_reminder_token);
        if (!$matches) {
            return false;
        }
        $since = new DateTime();
        $since->setTimezone(new DateTimeZone('Z'));
        $since->sub(new DateInterval('PT1H'));
        $reminderTime = new DateTime($this->data->u_reminder_time, new DateTimeZone('Z'));
        $wasRecently = $reminderTime > $since;
        $this->data->u_reminder_token = null;
        $this->data->u_reminder_time = null;
        $this->save();
        return $wasRecently;
    }

    public function checkPassword(string $password): bool
    {
        return password_verify($password, $this->data->u_password);
    }

    public function changePassword(string $password)
    {
        if ($this->getId() === false) {
            return false;
        }
        $sql = "UPDATE `users` SET password=:pwd, reminder_token=NULL, reminder_time=NULL WHERE id=:id";
        $params = [
            'pwd' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $this->getId(),
        ];
        $this->db->query($sql, $params);
    }

    public static function loadByUsername(string $name): User
    {
        $sql = "SELECT * FROM `users` JOIN `contacts` ON `u_contact` = `c_id` WHERE c_name = :name";
        $data = Database::getInstance()->query($sql, ['name' => $name])->fetch();
        return new self((object)$data);
    }

    public static function loadByEmail(string $email): User
    {
        $sql = "SELECT * FROM `users` JOIN `contacts` ON `u_contact` = `c_id` WHERE `u_email` = :email";
        $data = Database::getInstance()->query($sql, ['email' => $email])->fetch();
        return new self((object)$data);
    }

    public static function loadByEmailOrUserame(string $email, string $username): User
    {
        $sql = "SELECT * FROM `users` WHERE `u_email` = :email OR `u_username` = :username";
        $data = Database::getInstance()->query($sql, ['email' => $email, 'username' => $username])->fetch();
        return new self((object)$data);
    }

    public function getContact(): Contact
    {
        return Contact::loadById($this->data->u_contact);
    }

    public function getName()
    {
        return isset($this->data->c_name) ? $this->data->c_name : false;
    }

    public function getEmail()
    {
        return isset($this->data->u_email) ? $this->data->u_email : false;
    }

    /**
     * Get this user's default group.
     *
     * @return stdClass with attributes: 'id', 'name'.
     */
    public function getDefaultGroup()
    {
        $defaultGroupId = isset($this->data->default_group) ? $this->data->default_group : self::GROUP_PUBLIC;
        $sql = "SELECT * FROM groups WHERE id = :id";
        $group = $this->db->query($sql, ['id' => $defaultGroupId])->fetch();
        return $group;
    }
}
