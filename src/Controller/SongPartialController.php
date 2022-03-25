<?php

namespace App\Controller;

use App\Entity\Score;
use App\Entity\Song;
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
            ->where('s.wip != true')
            ->setMaxResults($this->count)
            ->setFirstResult(0)
            ->getQuery()->getResult();

        return $this->render('song_partial/index.html.twig', [
            'songs' => $songs
        ]);
    }

    public function lastPlayed(ScoreRepository $scoreRepository,SongRepository $songRepository): Response
    {
        $scores = $scoreRepository->createQueryBuilder("score")
            ->leftJoin("score.songDifficulty",'diff')
            ->leftJoin("diff.song",'s')
            ->orderBy('score.updatedAt', 'DESC')
            ->where('s.isDeleted != true')
            ->where('s.wip != true')
            ->setFirstResult(0)
            ->setMaxResults($this->count)
            ->getQuery()->getResult();
        $songs = array_map(function(Score $score){return $score->getSongDifficulty()->getSong();}, $scores);


        return $this->render('song_partial/index.html.twig', [
            'songs' => $songs
        ]);
    }

    public function topRated(SongRepository $songRepository): Response
    {
        $songs = $songRepository->createQueryBuilder("s")
            ->orderBy("s.voteUp - s.voteDown", 'DESC')
            ->where('s.isDeleted != true')
            ->where('s.wip != true')
            ->setMaxResults($this->count)
            ->setFirstResult(0)
            ->getQuery()->getResult();

        return $this->render('song_partial/index.html.twig', [
            'songs' => $songs
        ]);
    }
}
