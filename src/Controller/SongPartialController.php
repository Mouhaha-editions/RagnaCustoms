<?php

namespace App\Controller;

use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Repository\ScoreHistoryRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SongPartialController extends AbstractController
{

    private $count = 8;

    public function latestSongs(SongRepository $songRepository): Response
    {
        $songs = $songRepository->createQueryBuilder("s")
            ->orderBy("s.createdAt", 'DESC')
            ->where('s.isDeleted != true')
            ->andWhere('s.wip != true')
            ->setMaxResults($this->count)
            ->setFirstResult(0)
            ->getQuery()->getResult();

        return $this->render('song_partial/index.html.twig', [
            'songs' => $songs
        ]);
    }

    public function lastPlayed(ScoreHistoryRepository $scoreHistoryRepository,SongRepository $songRepository): Response
    {
        $scores = $scoreHistoryRepository->createQueryBuilder("score")
            ->leftJoin("score.songDifficulty",'diff')
            ->leftJoin("diff.song",'s')
            ->orderBy('score.updatedAt', 'DESC')
            ->where('s.isDeleted != true')
            ->andWhere('s.wip != true')
            ->setFirstResult(0)
            ->setMaxResults($this->count)
            ->getQuery()->getResult();
        $songs = array_map(function(ScoreHistory $score){return $score->getSongDifficulty()->getSong();}, $scores);


        return $this->render('song_partial/index.html.twig', [
            'songs' => $songs
        ]);
    }

    public function topRated(SongRepository $songRepository, $lastXDays = 99999): Response
    {
        $songs = $songRepository->createQueryBuilder("s")
            ->addSelect('s, SUM(IF(v.votes_indc IS NULL,0,IF(v.votes_indc = 0,-1,1))) AS HIDDEN sum_votes')
            ->leftJoin("s.voteCounters",'v')
            ->orderBy("sum_votes", 'DESC')
            ->where('s.isDeleted != true')
            ->andWhere('s.wip != true')
            ->andWhere('v.updatedAt >= :date')
            ->setParameter('date',(new \DateTime())->modify('-'.$lastXDays." days"))
            ->groupBy('s.id')
            ->setMaxResults($this->count)
            ->setFirstResult(0)
            ->getQuery()->getResult();

        return $this->render('song_partial/index.html.twig', [
            'songs' => $songs
        ]);
    }
}
