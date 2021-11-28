<?php

namespace App\Repository;

use App\Entity\ChallengePrize;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ChallengePrize|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChallengePrize|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChallengePrize[]    findAll()
 * @method ChallengePrize[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChallengePrizeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChallengePrize::class);
    }

    // /**
    //  * @return ChallengePrize[] Returns an array of ChallengePrize objects
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
    public function findOneBySomeField($value): ?ChallengePrize
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
