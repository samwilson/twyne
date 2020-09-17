<?php

namespace Samwilson\Twyne\Data;

use DateTime;
use Samwilson\Twyne\Database;
use stdClass;

class Post extends ModelBase
{

    public static function getTableName(): string
    {
        return 'posts';
    }

    public static function getTableAbbr(): string
    {
        return 'p';
    }

    public function __construct($data = null)
    {
        parent::__construct($data);
        if (!$this->getDatetime()) {
            $this->setDatetime(date('Y-m-d H:i:s'));
        }
    }

    public static function newForUser(User $user)
    {
        return new self((object)[
            'p_user' => $user->getId(),
            'p_author' => $user->getContact()->getId(),
        ]);
    }

    public static function getRecent($limit = 10)
    {
        $data = Database::getInstance()->query(
            'SELECT posts.*, authors.c_name AS author_name FROM `posts`'
            . ' JOIN contacts authors ON authors.c_id=posts.p_author'
            . ' ORDER BY `p_datetime` DESC LIMIT :limit',
            ['limit' => $limit]
        );
        $out = [];
        foreach ($data as $row) {
            $out[] = new Post($row);
        }
        return $out;
    }

    /**
     * Take a application-specific date string (that supports multiple precisions) and convert it into an array of
     * labelled components.
     * @param $date
     * @return array
     */
    public static function parseDate($date)
    {
        preg_match('/(c\.)?(\d+)-?(\d*)(s)?/', $date, $matches);
        $decade = isset($matches[4]) && $matches[4];
        $circa = isset($matches[1]) && $matches[1];
        $year = $matches[2] ?? null;
        $month = $matches[3] ?? null;
        return  [
            'decade' => $decade,
            'circa' => $circa,
            'year' => $year,
            'month' => $month,
        ];
    }

    public static function getDates($dateString = null)
    {
        $date = static::parseDate($dateString);
        $yearsSql = 'SELECT YEAR(`p_datetime`) AS `year`, COUNT(*) AS `count` '
            . 'FROM `posts` GROUP BY YEAR(`p_datetime`) ORDER BY `year` DESC';
        $monthsSql = 'SELECT'
            . '   MONTH(`p_datetime`) AS `month`,'
            . '   COUNT(*) AS `count` FROM `posts`'
            . ' WHERE YEAR(`p_datetime`) = :year GROUP BY MONTH(`p_datetime`) ORDER BY `month` DESC';
        $months = [];
        $db = Database::getInstance();
        foreach ($db->query($monthsSql, ['year' => $date['year']]) as $m) {
            $month = str_pad($m->month, 2, '0', STR_PAD_LEFT);
            $months[$month] = [
                'num' => $month,
                'name' => date_create($date['year'] . '-' . $month . '-15 12:00:00 Z')->format('F'),
                'count' => $m->count,
            ];
        }
        return [
            'selected' => $date,
            'months' => $months,
            'years' => $db->query($yearsSql)->fetchAll(),
        ];
    }

    public static function getByDate($dateString)
    {
        $date = static::parseDate($dateString);
        $sql = 'SELECT * FROM `posts` WHERE YEAR(`p_datetime`) = :year';
        $params = ['year' => $date['year']];
        if ($date['month']) {
            $sql .= ' AND MONTH(`p_datetime`) = :month';
            $params['month'] = $date['month'];
        }
        $rows = Database::getInstance()->query($sql, $params);
        $out = [];
        foreach ($rows as $row) {
            $out[] = new Post($row);
        }
        return $out;
    }

    public function canBeEditedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        $userExists = (bool)$user->getId();
        $postExists = (bool)$this->getId();
        $postBelongsToUser = $this->getUserId() === $user->getId();
        return $userExists && ($postExists && $postBelongsToUser || !$postExists);
    }

    public function getTitle(): string
    {
        return $this->data->p_title ?? '';
    }

    public function setTitle(string $title)
    {
        $this->data->p_title = trim($title);
    }

    public function getUserId(): ?int
    {
        return isset($this->data->p_user) ? (int)$this->data->p_user : null;
    }

    public function getUser(): ?User
    {
        return User::loadById($this->getUserId());
    }

    public function setUser(int $userId): void
    {
        $this->data->p_user = $userId;
    }

    public function setAuthor(Contact $author): void
    {
        $this->data->p_author = $author->getId();
    }

    public function getAuthorId(): ?int
    {
        return isset($this->data->p_author) ? (int)$this->data->p_author : null;
    }

    public function getAuthor(): ?Contact
    {
        if (!$this->getAuthorId()) {
            return null;
        }
        if (!isset($this->data->author_name) && $this->getAuthorId()) {
            return Contact::loadById($this->getAuthorId());
        }
        $contactData = new stdClass();
        $contactData->c_id = $this->getAuthorId();
        $contactData->c_name = $this->data->author_name;
        return new Contact($contactData);
    }

    /**
     * @param string $datetime
     */
    public function setDatetime($datetime)
    {
        $dt = new DateTime($datetime);
        $this->data->p_datetime = $dt->format('Y-m-d H:i:s');
    }

    public function getDatetime(): string
    {
        return isset($this->data->p_datetime) ? $this->data->p_datetime : false;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body)
    {
        $this->data->p_body = trim($body);
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return isset($this->data->p_body) ? $this->data->p_body : false;
    }
}
