<?php

namespace App\Repository;

use App\Entity\DownloadCounter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DownloadCounter|null find($id, $lockMode = null, $lockVersion = null)
 * @method DownloadCounter|null findOneBy(array $criteria, array $orderBy = null)
 * @method DownloadCounter[]    findAll()
 * @method DownloadCounter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DownloadCounterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DownloadCounter::class);
    }

    // /**
    //  * @return DownloadCounter[] Returns an array of DownloadCounter objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DownloadCounter
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
