<?php

namespace App\Repository;

use App\Entity\RankedScores;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RankedScores|null find($id, $lockMode = null, $lockVersion = null)
 * @method RankedScores|null findOneBy(array $criteria, array $orderBy = null)
 * @method RankedScores[]    findAll()
 * @method RankedScores[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RankedScoresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RankedScores::class);
    }
    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(RankedScores $entity, bool $flush = true): void
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
    public function remove(RankedScores $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
