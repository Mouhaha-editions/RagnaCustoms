<?php

namespace App\Repository;

use App\Entity\CustomEventParticipation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomEventParticipation>
 *
 * @method CustomEventParticipation|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomEventParticipation|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomEventParticipation[]    findAll()
 * @method CustomEventParticipation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomEventParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomEventParticipation::class);
    }

//    /**
//     * @return CustomEventParticipation[] Returns an array of CustomEventParticipation objects
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

//    public function findOneBySomeField($value): ?CustomEventParticipation
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
