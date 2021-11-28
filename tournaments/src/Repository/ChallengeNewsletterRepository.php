<?php

namespace App\Repository;

use App\Entity\ChallengeNewsletter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ChallengeNewsletter|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChallengeNewsletter|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChallengeNewsletter[]    findAll()
 * @method ChallengeNewsletter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChallengeNewsletterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChallengeNewsletter::class);
    }

    // /**
    //  * @return ChallengeNewsletter[] Returns an array of ChallengeNewsletter objects
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
    public function findOneBySomeField($value): ?ChallengeNewsletter
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
