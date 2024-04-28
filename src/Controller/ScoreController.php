<?php

namespace App\Controller;

use App\Entity\Country;
use App\Entity\Song;
use App\Repository\FriendRepository;
use App\Repository\RankedScoresRepository;
use App\Service\ScoreService;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ScoreController extends AbstractController
{
    #[Route(path: '/score/stats/{id}', name: 'score_stats')]
    public function getStats(Song $song, LoggerInterface $logger): Response
    {
        // $logger->error('Stat score');
        return new Response('',Response::HTTP_NOT_FOUND);
    }

    /**
     * @param  Request  $request
     * @param  Country  $country
     * @param  PaginationService  $pagination
     * @param  ScoreService  $scoreService
     * @param  RankedScoresRepository  $rankedScoresRepository
     * @return Response
     */
    #[Route(path: '/ranking/country/{twoLetters}', name: 'score_global_country')]
    public function globalCountryRanking(
        Request $request,
        Country $country,
        PaginationService $pagination,
        ScoreService $scoreService,
        RankedScoresRepository $rankedScoresRepository
    ): Response {
        if ($request->get('findme', null)) {
            $score = null;
            if ($this->isGranted('ROLE_USER') && $this->getUser()->getCountry()->getId() == $country->getId()) {
                $score = $scoreService->getGeneralLeaderboardPosition($this->getUser(), $country);
            }
            if ($score == null) {
                $this->addFlash("danger", "No score found");
                return $this->redirectToRoute("score_global_ranking");
            } else {
                return $this->redirect(
                    $this->generateUrl("score_global_ranking")."?ppage1=".ceil($score / 25)."#".$this->getUser(
                    )->getUsername()
                );
            }
        }

        $qb = $rankedScoresRepository
            ->createQueryBuilder('rs')->leftJoin('rs.user', 'u')
            ->leftJoin('u.country', 'c')->where('u.country = :country')
            ->setParameter('country', $country)
            ->andWhere('rs.plateform = :vr')
            ->setParameter('vr', 'vr')
            ->orderBy("rs.totalPPScore", "DESC");
        $scores = $pagination->setDefaults(25)->process($qb, $request);

        $qb = $rankedScoresRepository->createQueryBuilder('rs')->leftJoin('rs.user', 'u')
            ->leftJoin('u.country', 'c')
            ->where('u.country = :country')
            ->setParameter('country', $country)
            ->andWhere('rs.plateform = :flat')
            ->setParameter('flat', 'flat')
            ->orderBy("rs.totalPPScore", "DESC");
        $scoresFlat = $pagination->setDefaults(25)->process($qb, $request);

 $qb = $rankedScoresRepository->createQueryBuilder('rs')->leftJoin('rs.user', 'u')
            ->leftJoin('u.country', 'c')
            ->where('u.country = :country')
            ->setParameter('country', $country)
            ->andWhere('rs.plateform = :flat')
            ->setParameter('flat', 'flat_okod')
            ->orderBy("rs.totalPPScore", "DESC");
        $scoresFlatOkodo = $pagination->setDefaults(25)->process($qb, $request);

        return $this->render('score/global_ranking.html.twig', [
            'scores' => $scores,
            'scoresFlatClassic' => $scoresFlat,
            'scoresFlatOkodo' => $scoresFlatOkodo,
            'country' => $country
        ]);
    }

    /**
     * @param  Request  $request
     * @param  PaginationService  $pagination
     * @param  ScoreService  $scoreService
     * @param  RankedScoresRepository  $rankedScoresRepository
     * @return Response
     */
    #[Route(path: '/ranking/global', name: 'score_global_ranking')]
    public function globalRanking(
        Request $request,
        PaginationService $pagination,
        ScoreService $scoreService,
        RankedScoresRepository $rankedScoresRepository,
        FriendRepository $friendRepository
    ): Response {
        if ($request->get('findme', null)) {
            $score = $scoreService->getGeneralLeaderboardPosition($this->getUser());
            if ($score == null) {
                $this->addFlash("danger", "No score found");
                return $this->redirectToRoute("score_global_ranking");
            } else {
                return $this->redirect(
                    $this->generateUrl("score_global_ranking")."?ppage1=".ceil($score / 25)."#".$this->getUser(
                    )->getUsername()
                );
            }
        }

        $friends = [$this->getUser()];
        $friendRequests = $friendRepository->getMine($this->getUser());

        foreach ($friendRequests as $friendRequest) {
            $friends[] = $friendRequest->getOther($this->getUser());
        }

        $qb = $rankedScoresRepository->createQueryBuilder('rs')
            ->where('rs.plateform = :vr')
            ->setParameter("vr", 'vr')
            ->orderBy("rs.totalPPScore", "DESC");

        if ($request->query->get('only_friend_of_mine')) {
            $qb->andWhere('rs.user IN (:friends)')
                ->setParameter('friends', $friends);
        }

        $scores = $pagination->setDefaults(25)->process($qb, $request);

        if ($request->get('findme_flat', null)) {
            $scoreFlat = $scoreService->getGeneralLeaderboardPosition($this->getUser(), null, false);
            if ($scoreFlat == null) {
                $this->addFlash("danger", "No score found");
                return $this->redirectToRoute("score_global_ranking");
            } else {
                return $this->redirect(
                    $this->generateUrl("score_global_ranking")."?ppage2=".ceil($scoreFlat / 25)."#".$this->getUser(
                    )->getUsername()
                );
            }
        }

        $qb = $rankedScoresRepository->createQueryBuilder('rs')
            ->where('rs.plateform = :flat')
            ->setParameter('flat', 'flat')
            ->orderBy('rs.totalPPScore', 'DESC');

        if ($request->query->get('only_friend_of_mine')) {
            $qb->andWhere('rs.user IN (:friends)')
                ->setParameter('friends', $friends);
        }

        $scoresFlat = $pagination->setDefaults(25)->process($qb, $request);

        if ($request->get('findme_flat_okodo', null)) {
            $scoreFlat = $scoreService->getGeneralLeaderboardPosition($this->getUser(), null, false);
            if ($scoreFlat == null) {
                $this->addFlash("danger", "No score found");
                return $this->redirectToRoute("score_global_ranking");
            } else {
                return $this->redirect(
                    $this->generateUrl("score_global_ranking")."?ppage2=".ceil($scoreFlat / 25)."#".$this->getUser(
                    )->getUsername()
                );
            }
        }

        $qb = $rankedScoresRepository->createQueryBuilder('rs')
            ->where('rs.plateform = :flat')
            ->setParameter('flat', 'flat_okod')
            ->orderBy('rs.totalPPScore', 'DESC');

        if ($request->query->get('only_friend_of_mine')) {
            $qb->andWhere('rs.user IN (:friends)')
                ->setParameter('friends', $friends);
        }

        $scoresFlatOkodo = $pagination->setDefaults(25)->process($qb, $request);

        return $this->render('score/global_ranking.html.twig', [
            'scores' => $scores,
            'scoresFlatClassic' => $scoresFlat,
            'scoresFlatOkodo' => $scoresFlatOkodo
        ]);
    }
}
