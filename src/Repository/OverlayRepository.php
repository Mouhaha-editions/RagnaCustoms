<?php

namespace App\Repository;

use App\Entity\Overlay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Overlay|null find($id, $lockMode = null, $lockVersion = null)
 * @method Overlay|null findOneBy(array $criteria, array $orderBy = null)
 * @method Overlay[]    findAll()
 * @method Overlay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OverlayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Overlay::class);
    }

    // /**
    //  * @return Overlay[] Returns an array of Overlay objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Overlay
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
