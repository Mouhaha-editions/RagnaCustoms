<?php

namespace App\Repository;

use App\Entity\SongFeedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SongFeedback|null find($id, $lockMode = null, $lockVersion = null)
 * @method SongFeedback|null findOneBy(array $criteria, array $orderBy = null)
 * @method SongFeedback[]    findAll()
 * @method SongFeedback[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongFeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SongFeedback::class);
    }

    // /**
    //  * @return SongFeedback[] Returns an array of SongFeedback objects
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
    public function findOneBySomeField($value): ?SongFeedback
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
