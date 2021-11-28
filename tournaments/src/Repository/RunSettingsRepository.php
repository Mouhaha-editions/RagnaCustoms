<?php

namespace App\Repository;

use App\Entity\RunSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RunSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method RunSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method RunSettings[]    findAll()
 * @method RunSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RunSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RunSettings::class);
    }

    // /**
    //  * @return RunSettings[] Returns an array of RunSettings objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RunSettings
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
