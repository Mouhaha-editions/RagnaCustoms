<?php

namespace App\Repository;

use App\Entity\Score;
use App\Entity\Season;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Score|null find($id, $lockMode = null, $lockVersion = null)
 * @method Score|null findOneBy(array $criteria, array $orderBy = null)
 * @method Score[]    findAll()
 * @method Score[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Score::class);
    }
    

    public function findBySeasonDiffHash(?Season $season, $difficulty, $hash)
    {
        $qb = $this->createQueryBuilder("s")
            ->where("s.difficulty = :difficulty")
            ->andWhere("s.hash = :hash")
            ->setParameter("difficulty", $difficulty)
            ->setParameter("hash", $hash);
        if ($season !== null) {
            $qb->andWhere("s.season = :season")
                ->setParameter("season", $season);
        }
        $qb->orderBy('s.score','desc');
        return $qb->getQuery()->getResult();
    }

}
