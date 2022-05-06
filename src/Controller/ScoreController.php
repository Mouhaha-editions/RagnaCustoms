<?php

namespace App\Controller;

use App\Entity\Country;
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
     * @Route("/ranking/country/{twoLetters}", name="score_global_country")
     * @param Request $request
     * @param Country $country
     * @param PaginationService $pagination
     * @param ScoreService $scoreService
     * @param RankedScoresRepository $rankedScoresRepository
     * @return Response
     */
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
            'scores' => $scores,
            'country' => $country
        ]);
    }

    /**
     * @Route("/ranking/global", name="score_global_ranking")
     * @param Request $request
     * @param PaginationService $pagination
     * @param ScoreService $scoreService
     * @param RankedScoresRepository $rankedScoresRepository
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
     * @Route("/ranking/unrank/{id}", name="score_unrank")
     * @param Request $request
     * @param ScoreService $scoreService
     * @param RankedScoresRepository $rankedScoresRepository
     * @return Response
     */
    public function unrankScoreUpdate(Request                $request,
                                      ScoreService           $scoreService,
                                      ManagerRegistry $doctrine,
                                      ScoreRepository        $scoreRepository,
                                      RankedScoresRepository $rankedScoresRepository,
                                      SongDifficulty         $songDifficulty): Response
    {

        $em = $doctrine->getManager();

        //unrank the song
        $songDifficulty->setIsRanked(!$songDifficulty->isRanked());

        //get the score of everyone on this song
        $scores = $scoreRepository->createQueryBuilder('score')
            ->where('score.songDifficulty = :diff')
            ->setParameter('diff', $songDifficulty)
            ->getQuery()->getResult();

        //reset the raw PP score of the song
        foreach ($scores as $score) {
            $user = $score->getUser();
            $score->setRawPP(0);
            //update of the score into ranked_scores
            $rankedScore = $rankedScoresRepository->findOneBy([
                'user' => $user
            ]);
            $totalPondPPScore = $scoreService->calculateTotalPondPPScore($scoreRepository, $user);
            $rankedScore->setTotalPPScore($totalPondPPScore);
        }

        $em->flush();

        return new JsonResponse(['result' => $songDifficulty->isRanked() ? '<i class="fas fa-star"></i> ranked' : '<i class="far fa-star"></i> not r.']);

    }


}
