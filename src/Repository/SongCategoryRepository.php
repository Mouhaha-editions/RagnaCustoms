<?php

namespace App\Repository;

use App\Entity\SongCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SongCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method SongCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method SongCategory[]    findAll()
 * @method SongCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SongCategory::class);
    }

    // /**
    //  * @return SongCategory[] Returns an array of SongCategory objects
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
    public function findOneBySomeField($value): ?SongCategory
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
