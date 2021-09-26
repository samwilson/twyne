<?php

namespace App\Repository;

use App\Entity\Redirect;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Redirect|null find($id, $lockMode = null, $lockVersion = null)
 * @method Redirect|null findOneBy(array $criteria, array $orderBy = null)
 * @method Redirect[]    findAll()
 * @method Redirect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RedirectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Redirect::class);
    }

    public function getStatuses(): array
    {
        return [
            'permanent' => 301,
            'temp' => 302,
            'seeother' => 303,
            'gone' => 410,
        ];
    }

    public function addRedirect(string $path, ?string $destination, int $status)
    {
        $redirect = new Redirect();
        $redirect->setPath($path);
        $redirect->setDestination($destination);
        $redirect->setStatus($status);
        $this->getEntityManager()->persist($redirect);
        $this->getEntityManager()->flush();
    }

    public function saveFromRequest(Request $request): Redirect
    {
        $redirect = $request->get('id') ? $this->find($request->get('id')) : new Redirect();
        $redirect->setPath($request->get('path'));
        $redirect->setDestination($request->get('destination'));
        $redirect->setStatus($request->get('status'));
        $this->getEntityManager()->persist($redirect);
        $this->getEntityManager()->flush();
        return $redirect;
    }

    public function findOneByPath(string $path): ?Redirect
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.path = :val')
            ->setParameter('val', $path)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
