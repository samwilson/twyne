<?php

namespace App\Repository;

use App\Entity\Contact;
use App\Entity\User;
use App\Entity\UserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{

    /** @var UserGroupRepository */
    private $userGroupRepository;

    public function __construct(ManagerRegistry $registry, UserGroupRepository $userGroupRepository)
    {
        parent::__construct($registry, User::class);
        $this->userGroupRepository = $userGroupRepository;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findOrCreate(string $username): User
    {
        $existing = $this->findOneBy(['username' => $username]);
        if ($existing) {
            return $existing;
        }
        // If not found, create a new User and matching Contact.
        $user = new User();
        $user->setUsername($username);
        $user->setPassword('');
        $contact = new Contact();
        $contact->setName($username);
        $user->setContact($contact);
        $user->addGroup($this->userGroupRepository->find(UserGroup::PUBLIC));
        $this->getEntityManager()->persist($user);
        return $user;
    }
}
