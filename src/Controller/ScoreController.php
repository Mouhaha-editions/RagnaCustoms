<?php

namespace App\Controller;

use App\Entity\Country;
use App\Entity\Score;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Repository\RankedScoresRepository;
use App\Repository\ScoreRepository;
use App\Service\RankingScoreService;
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

            ->andWhere('rs.plateform = :vr')
            ->setParameter('flat', 'vr')
            ->orderBy("rs.totalPPScore", "DESC");
        $scores = $pagination->setDefaults(25)->process($qb, $request);

        $qb = $rankedScoresRepository->createQueryBuilder('rs')->leftJoin('rs.user', 'u')
            ->leftJoin('u.country', 'c')->where('u.country = :country')
            ->setParameter('country', $country)
            ->andWhere('rs.plateform = :flat')
            ->setParameter('flat', 'flat')
            ->orderBy("rs.totalPPScore", "DESC");
        $scoresFlat = $pagination->setDefaults(25)->process($qb, $request);

        return $this->render('score/global_ranking.html.twig', [
            'scores'  => $scores,
            'scoresFlat'  => $scoresFlat,
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

        $qb = $rankedScoresRepository->createQueryBuilder('rs')
            ->where('rs.plateform = :vr')
            ->setParameter("vr", 'vr')
            ->orderBy("rs.totalPPScore", "DESC");
        $scores = $pagination->setDefaults(25)->process($qb, $request);

        if ($request->get('findme_flat', null)) {
            $scoreFlat = $scoreService->getGeneralLeaderboardPosition($this->getUser(), false);
            if ($scoreFlat == null) {
                $this->addFlash("danger", "No score found");
                return $this->redirectToRoute("score_global_ranking");
            } else {
                return $this->redirect($this->generateUrl("score_global_ranking") . "?ppage2=" . ceil($scoreFlat / 25) . "#" . $this->getUser()->getUsername());
            }
        }

        $qb = $rankedScoresRepository->createQueryBuilder('rs')
            ->where('rs.plateform = :flat')
            ->setParameter('flat', 'flat')
            ->orderBy('rs.totalPPScore', 'DESC');
        $scoresFlat = $pagination->setDefaults(25)->process($qb, $request);


        return $this->render('score/global_ranking.html.twig', [
            'scores' => $scores,
            'scoresFlat'=> $scoresFlat
        ]);
    }
}
