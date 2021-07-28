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
    public function index(Request $request, SongRepository $songRepository, PaginationService $paginationService, SeasonRepository $seasonRepository): Response
    {
        $qb = $songRepository->createQueryBuilder('s')
            ->where('s.moderated = true')
            ->distinct()
            ->orderBy('s.name', 'ASC');
        $selectedSeason = null;
        if ($request->get('season')) {
            $qb->leftJoin('s.songDifficulties', 'difficulties')
                ->leftJoin('difficulties.seasons', 'season')
                ->andWhere('season.id = :season')
                ->setParameter('season', $request->get('season'));
            $selectedSeason = $seasonRepository->find($request->get('season'));
        }
        $songs = $paginationService->setDefaults(20)->process($qb, $request);

        if ($songs->isPartial()) {
            return $this->render('score/partial/songs_page.html.twig', [
                'songs' => $songs,
                'seasons' => $seasonRepository->createQueryBuilder('s')->orderBy('s.id', "desc")->getQuery()->getResult(),
                'selected_season' => $selectedSeason,

            ]);
        }
        return $this->render('score/index.html.twig', [
            'songs' => $songs,
            'seasons' => $seasonRepository->createQueryBuilder('s')->orderBy('s.id', "desc")->getQuery()->getResult(),
            'selected_season' => $selectedSeason,

        ]);
    }

    /**
     * @Route("/ranking/global/{id}", name="score_global_ranking", defaults={"id"=null})
     */
    public function globalRanking(Request $request, ScoreRepository $scoreRepository, SeasonRepository $seasonRepository, PaginationService $paginationService, Season $season = null): Response
    {
        $oldSeason = $seasonRepository->getOld();

        if ($season === null) {
            $season = $seasonRepository->getCurrent();
        }
        $qb = $scoreRepository->createQueryBuilder('s')
            ->select('u.username AS username,u.id AS user_id, MD5(LOWER(u.email)) as gravatar, SUM(s.score)/1000 AS score, COUNT(s.songDifficulty) AS count_song')
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
