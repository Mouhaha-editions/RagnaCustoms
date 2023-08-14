<?php

namespace App\Repository;

use App\Entity\CustomEventScore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomEventScore>
 *
 * @method CustomEventScore|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomEventScore|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomEventScore[]    findAll()
 * @method CustomEventScore[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomEventScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomEventScore::class);
    }

//    /**
//     * @return CustomEventScore[] Returns an array of CustomEventScore objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CustomEventScore
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
