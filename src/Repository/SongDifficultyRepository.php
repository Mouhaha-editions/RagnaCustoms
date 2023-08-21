<?php

namespace App\Repository;

use App\Entity\SongDifficulty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SongDifficulty|null find($id, $lockMode = null, $lockVersion = null)
 * @method SongDifficulty|null findOneBy(array $criteria, array $orderBy = null)
 * @method SongDifficulty[]    findAll()
 * @method SongDifficulty[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongDifficultyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SongDifficulty::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(SongDifficulty $entity, bool $flush = false): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(SongDifficulty $entity, bool $flush = false): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
    // /**
    //  * @return SongDifficulty[] Returns an array of SongDifficulty objects
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
    public function findOneBySomeField($value): ?SongDifficulty
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @return SongDifficulty[]
     */
    public function getRanked(): array
    {
        $qb = $this->createQueryBuilder('sd')
            ->leftJoin('sd.song', 's')
            ->andWhere("sd.isRanked = true")
            ->orderBy('s.name', 'ASC');

       return $qb->getQuery()->getResult();
    }
}
