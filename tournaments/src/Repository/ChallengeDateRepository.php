<?php

namespace App\Repository;

use App\Entity\ChallengeDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ChallengeDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChallengeDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChallengeDate[]    findAll()
 * @method ChallengeDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChallengeDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChallengeDate::class);
    }

    // /**
    //  * @return ChallengeDate[] Returns an array of ChallengeDate objects
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
    public function findOneBySomeField($value): ?ChallengeDate
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
