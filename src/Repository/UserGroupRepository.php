<?php

namespace App\Repository;

use App\Entity\UserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserGroup[]    findAll()
 * @method UserGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGroup::class);
    }

    public function findOrCreate(string $name): UserGroup
    {
        $existing = $this->findOneBy(['name' => $name]);
        if ($existing) {
            return $existing;
        }
        // If not found, create a new User and matching Contact.
        $group = new UserGroup();
        $group->setName($name);
        $this->getEntityManager()->persist($group);
        $this->getEntityManager()->flush();
        return $group;
    }
}
