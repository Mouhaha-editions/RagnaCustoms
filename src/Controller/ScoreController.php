<?php

namespace App\Controller;

use App\Entity\Country;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Repository\RankedScoresRepository;
use App\Repository\ScoreRepository;
use App\Service\ScoreService;
use Doctrine\Persistence\ManagerRegistry;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ScoreController extends AbstractController
{
    /**
     * @return void
     */
    #[Route(path: '/score/stats/{id}', name: 'score_stats')]
    public function getStats(Song $song)
    {

    }

    /**
     * @param Request $request
     * @param Country $country
     * @param PaginationService $pagination
     * @param ScoreService $scoreService
     * @param RankedScoresRepository $rankedScoresRepository
     * @return Response
     */
    #[Route(path: '/ranking/country/{twoLetters}', name: 'score_global_country')]
    public function globalCountryRanking(Request $request, Country $country, PaginationService $pagination, ScoreService $scoreService, RankedScoresRepository $rankedScoresRepository): Response
    {
        if ($request->get('findme', null)) {
            $score = null;
            if ($this->isGranted('ROLE_USER') && $this->getUser()->getCountry()->getId() == $country->getId()) {
                $score = $scoreService->getGeneralLeaderboardPosition($this->getUser(), $country);
            }
            if ($score == null) {
                $this->addFlash("danger", "No score found");
                return $this->redirectToRoute("score_global_ranking");
            } else {
                return $this->redirect($this->generateUrl("score_global_ranking") . "?ppage1=" . ceil($score / 25) . "#" . $this->getUser()->getUsername());
            }
        }

        $qb = $rankedScoresRepository->createQueryBuilder('rs')->leftJoin('rs.user', 'u')
                                     ->leftJoin('u.country', 'c')->where('u.country = :country')
                                     ->setParameter('country', $country)
                                     ->orderBy("rs.totalPPScore", "DESC");
        $scores = $pagination->setDefaults(25)->process($qb, $request);
        return $this->render('score/global_ranking.html.twig', [
            'scores'  => $scores,
            'country' => $country
        ]);
    }

    /**
     * @param Request $request
     * @param PaginationService $pagination
     * @param ScoreService $scoreService
     * @param RankedScoresRepository $rankedScoresRepository
     * @return Response
     */
    #[Route(path: '/ranking/global', name: 'score_global_ranking')]
    public function globalRanking(Request $request, PaginationService $pagination, ScoreService $scoreService, RankedScoresRepository $rankedScoresRepository): Response
    {
        if ($request->get('findme', null)) {
            $score = $scoreService->getGeneralLeaderboardPosition($this->getUser());
            if ($score == null) {
                $this->addFlash("danger", "No score found");
                return $this->redirectToRoute("score_global_ranking");
            } else {
                return $this->redirect($this->generateUrl("score_global_ranking") . "?ppage1=" . ceil($score / 25) . "#" . $this->getUser()->getUsername());
            }
        }

        $qb = $rankedScoresRepository->createQueryBuilder('rs')->orderBy("rs.totalPPScore", "DESC");
        $scores = $pagination->setDefaults(25)->process($qb, $request);
        return $this->render('score/global_ranking.html.twig', [
            'scores' => $scores,
        ]);
    }

    /**
     * @param Request $request
     * @param ScoreService $scoreService
     * @param RankedScoresRepository $rankedScoresRepository
     * @return Response
     */
    #[Route(path: '/ranking/toggle/{id}', name: 'rank_toggle')]
    public function toggleRankScore(Request                $request,
                                    ScoreService           $scoreService,
                                    ManagerRegistry        $doctrine,
                                    ScoreRepository        $scoreRepository,
                                    RankedScoresRepository $rankedScoresRepository,
                                    SongDifficulty         $songDifficulty,
                                    RankingScoreService    $rankingScoreService): Response
    {

        $em = $doctrine->getManager();

        //unrank or rank the song
        $songDifficulty->setIsRanked(!$songDifficulty->isRanked());
        $em->flush();

        //get the score of everyone on this song
        $scores = $scoreRepository->createQueryBuilder('score')
                                  ->where('score.songDifficulty = :diff')
                                  ->setParameter('diff', $songDifficulty)
                                  ->getQuery()->getResult();

        //if we rank then calculate the raw PP of the song
        //if we unrank then reset the raw PP score of the song
        foreach ($scores as $score) {

            $user = $score->getUser();
            if (!$score->getSongDifficulty()->isRanked()) {
                $score->setRawPP(0);
            } else {
                //calcul du rawPP + definir car on est ranked
                $score->setRawPP($rankingScoreService->calculateRawPP($score));
            }
            //update of the score into ranked_scores
            $rankedScore = $rankedScoresRepository->findOneBy([
                'user' => $user
            ]);
            if ($rankedScore != null) {
                $totalPondPPScore = $rankingScoreService->calculateTotalPondPPScore($scoreRepository, $user);
                $rankedScore->setTotalPPScore($totalPondPPScore);
            }

        }

        $em->flush();

        return new JsonResponse(['result' => $songDifficulty->isRanked() ? '<i class="fas fa-star"></i> ranked' : '<i class="far fa-star"></i> not r.']);

    }


}
