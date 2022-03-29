<?php

namespace App\Controller;

use App\Entity\Country;
use App\Entity\Season;
use App\Repository\RankedScoresRepository;
use App\Repository\ScoreRepository;
use App\Repository\SeasonRepository;
use App\Repository\SongRepository;
use App\Service\ScoreService;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ScoreController extends AbstractController
{
    /**
     * @Route("/leaderboard/{slug}", name="score", defaults={"slug"=null})
     * @param Request $request
     * @param SongRepository $songRepository
     * @param PaginationService $paginationService
     * @param SeasonRepository $seasonRepository
     * @param Season|null $selectedSeason
     * @return Response
     */
    public function index(Request $request, SongRepository $songRepository, PaginationService $paginationService, SeasonRepository $seasonRepository, Season $selectedSeason = null): Response
    {
        $qb = $songRepository->createQueryBuilder('s')
            ->where('s.moderated = true')
            ->andWhere('s.wip != true')
            ->andWhere("s.isDeleted != true")
            ->distinct()
            ->orderBy('s.name', 'ASC');

        if ($request->get('season', null) !== null) {
            if ($request->get('season') == 0) {
                return $this->redirectToRoute('score');
            }
            $selectedSeason = $seasonRepository->find($request->get('season'));
            return $this->redirectToRoute('score', ['slug' => $selectedSeason->getSlug()]);
        }
        if ($selectedSeason != null) {
            $qb->leftJoin('s.songDifficulties', 'difficulties')
                ->leftJoin('difficulties.seasons', 'season')
                ->andWhere('season.id = :season')
                ->setParameter('season', $selectedSeason);
        }
        $songs = $paginationService->setDefaults(50)->process($qb, $request);

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
     * @Route("/ranking/country/{twoLetters}", name="score_global_country")
     * @return Response
     */
    public function globalCountryRanking(Request $request,Country $country, PaginationService $pagination, ScoreService $scoreService, RankedScoresRepository $rankedScoresRepository): Response
    {
        if ($request->get('findme', null)) {
            $score = $scoreService->getGeneralLeaderboardPosition($this->getUser(), $country);
            if ($score == null) {
                $this->addFlash("danger", "No score found");
                return $this->redirectToRoute("score_global_ranking");
            } else {
                return $this->redirect($this->generateUrl("score_global_ranking") . "?ppage1=" . ceil($score / 25) . "#".$this->getUser()->getUsername());
            }
        }

        $qb = $rankedScoresRepository->createQueryBuilder('rs')
            ->leftJoin('rs.user','u')
            ->leftJoin('u.country','c')
            ->where('u.country = :country')
            ->setParameter('country', $country)
            ->orderBy("rs.totalPPScore", "DESC");
        $scores = $pagination->setDefaults(25)->process($qb, $request);
        return $this->render('score/global_ranking.html.twig', [
            'scores' => $scores,
            'country'=>$country
        ]);
    }

    /**
     * @Route("/ranking/global", name="score_global_ranking")
     * @return Response
     */
    public function globalRanking(Request $request, PaginationService $pagination, ScoreService $scoreService, RankedScoresRepository $rankedScoresRepository): Response
    {
        if ($request->get('findme', null)) {
            $score = $scoreService->getGeneralLeaderboardPosition($this->getUser());
            if ($score == null) {
                $this->addFlash("danger", "No score found");
                return $this->redirectToRoute("score_global_ranking");
            } else {
                return $this->redirect($this->generateUrl("score_global_ranking") . "?ppage1=" . ceil($score / 25) . "#".$this->getUser()->getUsername());
            }
        }

        $qb = $rankedScoresRepository->createQueryBuilder('rs')
            ->orderBy("rs.totalPPScore", "DESC");
        $scores = $pagination->setDefaults(25)->process($qb, $request);
        return $this->render('score/global_ranking.html.twig', [
            'scores' => $scores,
        ]);
    }

    /**
     * @Route("/ranking/{slug}", name="score_season_ranking", defaults={"slug"=null})
     */
    public function seasonRanking(Request $request, ScoreRepository $scoreRepository, SeasonRepository $seasonRepository, PaginationService $paginationService, Season $season = null): Response
    {
        $oldSeason = $seasonRepository->getOld();

        if ($season === null) {
            $season = $seasonRepository->getCurrent();
        }
        $qb = $scoreRepository->createQueryBuilder('s')
            ->select('u.username AS username,
            u.id AS user_id, 
            MD5(LOWER(u.email)) as gravatar, 
            SUM(s.score)/1000 AS score, 
            COUNT(s.hash) AS count_song')
            ->leftJoin('s.user', 'u')
            ->andWhere('s.season = :season')
            ->setParameter('season', $season)
            ->groupBy('s.user')
            ->orderBy('SUM(s.score)', 'DESC');
//        $scores = $qb->getQuery()->getResult();
        $scores = $paginationService->setDefaults(200)->process($qb, $request);

        return $this->render('score/season_ranking.html.twig', [
            'scores' => $scores,
            'season' => $season,
            'oldSeasons' => $oldSeason,
        ]);
    }


}
