<?php

namespace App\Repository;

use App\Entity\SongTemporaryList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SongTemporaryList|null find($id, $lockMode = null, $lockVersion = null)
 * @method SongTemporaryList|null findOneBy(array $criteria, array $orderBy = null)
 * @method SongTemporaryList[]    findAll()
 * @method SongTemporaryList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongTemporaryListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SongTemporaryList::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(SongTemporaryList $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(SongTemporaryList $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return SongTemporaryList[] Returns an array of SongTemporaryList objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SongTemporaryList
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
