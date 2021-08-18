<?php

namespace App\Repository;

use App\Entity\Gamification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Gamification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Gamification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Gamification[]    findAll()
 * @method Gamification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GamificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gamification::class);
    }

    // /**
    //  * @return Gamification[] Returns an array of Gamification objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Gamification
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
