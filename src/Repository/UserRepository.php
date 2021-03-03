<?php

namespace App\Repository;

use App\Entity\Contact;
use App\Entity\User;
use App\Entity\UserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Otp\Otp;
use ParagonIE\ConstantTime\Encoding as ConstantTimeEncoding;
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

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @param string $secret
     * @param string $key
     * @return bool
     */
    public function checkTwoFA(string $secret, string $key): bool
    {
        $sanitizedKey = preg_replace('/[^0-9]/', '', $key);
        $otp = new Otp();
        return $otp->checkTotp(ConstantTimeEncoding::base32DecodeUpper($secret), $sanitizedKey);
    }
}
