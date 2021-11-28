<?php

namespace App\Repository;

use App\Entity\CmsBlock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CmsBlock|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsBlock|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsBlock[]    findAll()
 * @method CmsBlock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsBlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CmsBlock::class);
    }

    // /**
    //  * @return Challenge[] Returns an array of Challenge objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Challenge
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
