<?php

namespace App\Repository;

use App\Entity\Song;
use App\Entity\SongHash;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SongHash|null find($id, $lockMode = null, $lockVersion = null)
 * @method SongHash|null findOneBy(array $criteria, array $orderBy = null)
 * @method SongHash[]    findAll()
 * @method SongHash[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongHashRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SongHash::class);
    }

    // /**
    //  * @return SongHash[] Returns an array of SongHash objects
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
    public function getLastVersion(Song $song)
    {
        /** @var SongHash $hash */
        $hash = $this->createQueryBuilder('s')
            ->andWhere('s.song = :song')
            ->setParameter('song', $song)
            ->orderBy('s.version', 'desc')
            ->getQuery()
            ->setMaxResults(1)
            ->setFirstResult(0)
            ->getOneOrNullResult();
        if ($hash !== null) {
            return $hash->getVersion();
        }
        return 0;
    }
    /*
    public function findOneBySomeField($value): ?SongHash
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
