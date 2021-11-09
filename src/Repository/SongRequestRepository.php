<?php

namespace App\Repository;

use App\Entity\SongRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SongRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method SongRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method SongRequest[]    findAll()
 * @method SongRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SongRequest::class);
    }

    // /**
    //  * @return SongRequest[] Returns an array of SongRequest objects
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
    public function findOneBySomeField($value): ?SongRequest
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
