<?php

namespace App\Repository;

use App\Entity\Contact;
use App\Entity\User;
use App\Entity\UserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Contact|null find($id, $lockMode = null, $lockVersion = null)
 * @method Contact|null findOneBy(array $criteria, array $orderBy = null)
 * @method Contact[]    findAll()
 * @method Contact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function getOrCreate(string $authorName)
    {
        $contact = $this->findOneBy(['name' => $authorName]);
        if (!$contact) {
            $contact = new Contact();
            $contact->setName($authorName);
            $this->getEntityManager()->persist($contact);
        }
        return $contact;
    }

    public function getRecentPosts(Contact $contact, int $num, ?User $user = null): array
    {
        $groupList = $user ? $user->getGroupIdList() : UserGroup::PUBLIC;
        $sql = "SELECT id, title, url
            FROM post
            WHERE author_id = :author_id AND view_group_id IN ($groupList)
            ORDER BY date DESC
            LIMIT $num";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue('author_id', $contact->getId());
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}
