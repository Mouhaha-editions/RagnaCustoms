<?php

namespace App\Repository;

use App\Entity\ScoreHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ScoreHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScoreHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScoreHistory[]    findAll()
 * @method ScoreHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScoreHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScoreHistory::class);
    }

}
