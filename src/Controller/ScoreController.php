<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\Song;
use App\Repository\ScoreRepository;
use App\Repository\SeasonRepository;
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
     * @param Request $request
     * @param SongRepository $songRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    public function index(Request $request, SongRepository $songRepository, PaginationService $paginationService): Response
    {
        $qb = $songRepository->createQueryBuilder('s')
            ->where('s.moderated = true')
            ->orderBy('s.name', 'ASC');
        $songs = $paginationService->setDefaults(20)->process($qb,$request);

        if($songs->isPartial()){
            return $this->render('score/partial/songs_page.html.twig', [
                'songs' => $songs
            ]);
        }
        return $this->render('score/index.html.twig', [
            'songs' => $songs
        ]);
    }

    /**
     * @Route("/ranking/global/{id}", name="score_global_ranking", defaults={"id"=null})
     */
    public function globalRanking(Request $request, ScoreRepository $scoreRepository, SeasonRepository $seasonRepository, PaginationService $paginationService,Season $season = null): Response
    {
        $oldSeason = $seasonRepository->getOld();

        if($season === null) {
            $season = $seasonRepository->getCurrent();
        }
        $qb = $scoreRepository->createQueryBuilder('s')
            ->select('u.username AS username, SUM(s.score)/1000 AS score, COUNT(s.songDifficulty) AS count_song')
            ->leftJoin('s.user', 'u')
            ->andWhere('s.season = :season')
            ->setParameter('season', $season)
            ->groupBy('s.user')
            ->orderBy('SUM(s.score)', 'DESC');
//        $scores = $qb->getQuery()->getResult();
        $scores = $paginationService->setDefaults(200)->process($qb, $request);

        return $this->render('score/global_ranking.html.twig', [
            'scores' => $scores,
            'season' => $season,
            'oldSeasons' => $oldSeason,
        ]);
    }
}
