<?php

namespace App\Repository;

use App\Entity\SongRequestVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SongRequestVote|null find($id, $lockMode = null, $lockVersion = null)
 * @method SongRequestVote|null findOneBy(array $criteria, array $orderBy = null)
 * @method SongRequestVote[]    findAll()
 * @method SongRequestVote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongRequestVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SongRequestVote::class);
    }

    // /**
    //  * @return SongRequestVote[] Returns an array of SongRequestVote objects
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
    public function findOneBySomeField($value): ?SongRequestVote
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
