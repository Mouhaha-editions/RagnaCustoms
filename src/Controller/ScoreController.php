<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\ScoreRepository;
use App\Repository\SongRepository;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ScoreController extends AbstractController
{
    /**
     * @Route("/leaderboard", name="score")
     */
    public function index(SongRepository $songRepository): Response
    {
        $songs = $songRepository->createQueryBuilder('s')
            ->where('s.moderated = true')
            ->orderBy('s.name', 'ASC')->getQuery()->getResult();


        return $this->render('score/index.html.twig', [
            'songs' => $songs
        ]);
    }

    /**
     * @Route("/ranking/global", name="score_global_ranking")
     */
    public function globalRanking(Request $request, ScoreRepository $scoreRepository, PaginationService $paginationService): Response
    {
        $qb = $scoreRepository->createQueryBuilder('s')
            ->select('u.username AS username, SUM(s.score)/1000 AS score, COUNT(s.song) AS count_song')
            ->leftJoin('s.user', 'u')
            ->groupBy('s.user')
            ->orderBy('SUM(s.score)', 'DESC');
        $scores = $paginationService->setDefaults(200)->process($qb, $request);

        return $this->render('score/global_ranking.html.twig', [
            'scores' => $scores
        ]);
    }
}
