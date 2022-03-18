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

    public function latestSongs(SongRepository $songRepository): Response
    {
        $songs = $songRepository->createQueryBuilder("s")
            ->orderBy("s.createdAt", 'DESC')
            ->where('s.isDeleted != true')
            ->where('s.wip != true')
            ->setMaxResults(10)
            ->setFirstResult(0)
            ->getQuery()->getResult();

        return $this->render('song_partial/index.html.twig', [
            'songs' => $songs
        ]);
    }

    public function lastPlayed(ScoreRepository $scoreRepository,SongRepository $songRepository): Response
    {
        $scores = $scoreRepository->createQueryBuilder("score")
            ->leftJoin("score.song",'s')
            ->orderBy('score.updatedAt', 'DESC')
            ->where('s.isDeleted != true')
            ->where('s.wip != true')
            ->setFirstResult(0)
            ->setMaxResults(10)
            ->getQuery()->getResult();
        $songs = array_map(function(Score $score){return $score->getSong();}, $scores);


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
            ->setMaxResults(10)
            ->setFirstResult(0)
            ->getQuery()->getResult();

        return $this->render('song_partial/index.html.twig', [
            'songs' => $songs
        ]);
    }
}
