<?php

namespace Samwilson\Twyne\Data;

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

    public static function newForUser(User $user): Contact
    {
        return new self((object)[
            'c_user' => $user->getId(),
        ]);
    }

    public function canBeEditedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        $userExists = (bool)$user->getId();
        $contactExists = (bool)$this->getId();
        $contactBelongsToUser = $this->getUserId() === $user->getId();
        return $userExists && ($contactExists && $contactBelongsToUser || !$contactExists);
    }

    public function canBeViewedBy(?User $user)
    {
        $hasPosts = count($this->getPosts()) > 0;
        return $hasPosts || $this->canBeEditedBy($user);
    }

    /**
     * @return Post[]
     */
    public function getPosts()
    {
        $postData = $this->db->query('SELECT * FROM posts WHERE p_author = :id', ['id' => $this->getId()]);
        $posts = [];
        foreach ($postData as $post) {
            $posts[] = new Post((object)$post);
        }
        return $posts;
    }

    public static function getByUserAndName(User $user, string $name)
    {
        $sql = 'SELECT * FROM contacts WHERE c_user = :user AND c_name LIKE :name';
        $contactData = Database::getInstance()->query($sql, ['user' => $user->getId(), 'name' => $name])->fetch();
        if (!$contactData) {
            $contactData = [
                'c_user' => $user->getId(),
                'c_name' => $name,
            ];
        }
        return new static((object)$contactData);
    }

    public function getName(): ?string
    {
        return $this->data->c_name ?? null;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->data->c_name = trim($name);
    }

    public function getDescription(): ?string
    {
        return $this->data->c_description ?? null;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->data->c_description = trim($description);
    }

    public function getUserId(): ?int
    {
        return isset($this->data->c_user) ? (int)$this->data->c_user : null;
    }

    public function getUser(): ?User
    {
        return User::loadById($this->getUserId());
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
