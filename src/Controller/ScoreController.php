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
use Symfony\Component\VarDumper\VarDumper;

class ScoreController extends AbstractController
{
    /**
     * @Route("/leaderboard/{slug}", name="score", defaults={"slug"=null})
     * @param Request $request
     * @param SongRepository $songRepository
     * @param PaginationService $paginationService
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

        if ($request->get('season',null) !== null) {
            if($request->get('season') ==0){
                return $this->redirectToRoute('score');
            }
            $selectedSeason = $seasonRepository->find($request->get('season'));
            return $this->redirectToRoute('score',['slug'=>$selectedSeason->getSlug()]);
        }
        if($selectedSeason!= null) {
            $qb->leftJoin('s.songDifficulties', 'difficulties')
                ->leftJoin('difficulties.seasons', 'season')
                ->andWhere('season.id = :season')
                ->setParameter('season',$selectedSeason);
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
     * @Route("/ranking/global", name="score_global_ranking")
     * @return Response
     */
    public function globalRanking(): Response
    {

        $conn = $this->getDoctrine()->getConnection();

        $sql = '
           SELECT SUM(max_score)/1000 AS score, 
                  username,
                  user_id,
                  MD5(LOWER(email)) as gravatar, 
                  COUNT(*) AS count_song 
           FROM (
                SELECT u.username,
                       u.email,
                       s.user_id, 
                       MAX(s.score) AS max_score 
                FROM score s 
                    LEFT JOIN song sg ON sg.new_guid = s.hash 
                    LEFT JOIN utilisateur u on s.user_id = u.id        
                WHERE sg.id IS NOT null and sg.wip != true
                GROUP BY s.hash,s.difficulty,s.user_id
            ) AS ms  GROUP BY user_id ORDER BY score DESC';
        VarDumper::dump($sql);
        $stmt = $conn->prepare($sql);
        $scores = $stmt->execute()->fetchAllAssociative();
//        VarDumper::dump(
//        $scores = $stmt->fetchAllAssociative();

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
