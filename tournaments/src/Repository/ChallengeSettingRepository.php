<?php

namespace App\Repository;

use App\Entity\ChallengeSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ChallengeSetting|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChallengeSetting|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChallengeSetting[]    findAll()
 * @method ChallengeSetting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChallengeSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChallengeSetting::class);
    }

    // /**
    //  * @return ChallengeSetting[] Returns an array of ChallengeSetting objects
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
    public function findOneBySomeField($value): ?ChallengeSetting
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
