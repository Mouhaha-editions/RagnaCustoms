<?php

namespace App\Service;

use App\Entity\Score;
use App\Entity\Season;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use App\Repository\ScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use ZipArchive;

class ScoreService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getMine(Utilisateur $user, SongDifficulty $songDifficulty, ?Season $season)
    {
        $return = [];
        /** @var Score $score */
        $qb = $this->em->getRepository(Score::class)->createQueryBuilder("s")
            ->where('s.user = :user')
            ->andWhere('s.songDifficulty = :songDifficulty')
            ->setParameter('user', $user)
            ->setParameter('songDifficulty', $songDifficulty)
           ;
        if ($season) {
            $qb->andWhere('s.season = :season')
                ->setParameter('season', $season);
        }

        $score = $qb ->setFirstResult(0)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
        if ($score == null) {
            return null;
        }
        $return['score'] = $score;

        $qb = $this->em->getRepository(Score::class)->createQueryBuilder("s")
            ->where('s.songDifficulty = :songDifficulty')
            ->andWhere("s.score >= :score")
            ->andWhere("s.user != :user")
            ->setParameter('score', $score->getScore())
            ->setParameter('user', $user)
            ->setParameter('songDifficulty', $songDifficulty);
             if ($season) {
                 $qb->andWhere('s.season = :season')
                     ->setParameter('season', $season);
             }
           $otherScore= $qb->getQuery()->getResult();
        $return['place'] = count($otherScore) + 1;
        return $return;
    }
}

