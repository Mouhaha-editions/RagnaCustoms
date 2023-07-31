<?php

namespace App\Controller;

use App\Entity\RankedScores;
use App\Entity\Score;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use App\Repository\RankedScoresRepository;
use App\Repository\ScoreHistoryRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongDifficultyRepository;
use App\Repository\UtilisateurRepository;
use App\Service\RankingScoreService;
use App\Service\ScoreService;
use Doctrine\Persistence\ManagerRegistry;
use Sentry\State\Scope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use function Sentry\configureScope;


class WanadevApiController extends AbstractController
{


    #[Route(path: '/wanapi/score/{apiKey}/{osef}-{hash}', name: 'wd_api_score_simple_get', methods: ['GET', 'POST'])]
    public function scoreSimple(
        Request $request,
        ManagerRegistry $doctrine,
        string $apiKey,
        string $hash,
        SongDifficultyRepository $songDifficultyRepository,
        UtilisateurRepository $utilisateurRepository,
        RankingScoreService $rankingScoreService,
        ScoreService $scoreService,
        RankedScoresRepository $rankedScoresRepository,
        ScoreRepository $scoreRepository,
        ScoreHistoryRepository $scoreHistoryRepository,
        $onlyMe = true
    ): Response {
        return $this->score(
            $request,
            $doctrine,
            $apiKey,
            $hash,
            $songDifficultyRepository,
            $utilisateurRepository,
            $rankingScoreService,
            $scoreService,
            $rankedScoresRepository,
            $scoreRepository,
            $scoreHistoryRepository,
            false
        );
    }

    #[Route(path: '/wanapi/score/{apiKey}/{osef}-{hash}/{oseftoo}/{oseftootoo}', name: 'wd_api_score_get', methods: [
        'GET',
        'POST'
    ])]
    public function score(
        Request $request,
        ManagerRegistry $doctrine,
        string $apiKey,
        string $hash,
        SongDifficultyRepository $songDifficultyRepository,
        UtilisateurRepository $utilisateurRepository,
        RankingScoreService $rankingScoreService,
        ScoreService $scoreService,
        RankedScoresRepository $rankedScoresRepository,
        ScoreRepository $scoreRepository,
        ScoreHistoryRepository $scoreHistoryRepository,
        $onlyMe = true
    ): Response {
        /** @var Utilisateur $user */
        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
        if ($user == null) {
            return new JsonResponse("NOK USER", 400);
        }
        configureScope(function (Scope $scope) use ($user): void {
            $scope->setUser(['username' => $user->getUsername()]);
        });
        /** @var SongDifficulty $songDiff */
        $songDiff = $songDifficultyRepository->findOneBy(['wanadevHash' => $hash]);
        if ($songDiff == null) {
            return new JsonResponse("NOK DIFF", 400);
        }

        if ($request->isMethod("post")) {
            $data = json_decode($request->getContent(), true);
            $em = $doctrine->getManager();

            $newScore = new Score();
            $newScore->setUser($user);
            $newScore->setSongDifficulty($songDiff);
            $newScore->setScore($data['score']);
//            $newScore->setDateRagnarock($data['created_at']);
            $newScore->setSession($data['session']);
            $newScore->setCountry($data['country']);
            $newScore->setUserRagnarock($data['user']);
            $newScore->setPlateform($data['platform'] ?? "");
            $newScore->setComboBlue($data['stats']['ComboBlue']);
            $newScore->setComboYellow($data['stats']['ComboYellow']);
            $newScore->setHit($data['stats']['Hit']);
            $newScore->setHitDeltaAverage($data['stats']['HitDeltaAverage']);
            $newScore->setHitPercentage($data['stats']['HitPercentage']);
            $newScore->setMissed($data['stats']['Missed']);
            $newScore->setExtra(json_encode($data['extra']));
            $newScore->setPercentageOfPerfects($data['stats']['PercentageOfPerfects']);
            if ($newScore->isRankable()) {
                $rawPP = $rankingScoreService->calculateRawPP($newScore);
                $newScore->setRawPP($rawPP);
            }

            /** @var Score $score */
            $scoreQb = $scoreRepository
                ->createQueryBuilder('s')
                ->where('s.user = :user')
                ->setParameter('user', $user)
                ->andWhere('s.songDifficulty = :songDifficulty')
                ->setParameter('songDifficulty', $songDiff)
                ->setParameter('plateform', 'Steam_Flat');

            if (in_array($newScore->getPlateform(), ['Steam_Flat'])) {
                $scoreQb->andWhere('s.plateform = :plateform');
            } else {
                $scoreQb->andWhere('s.plateform != :plateform');
            }

            $score = $scoreQb->getQuery()->getOneOrNullResult();
            $scoreService->archive($newScore);

            if ($score == null || $score->getScore() <= $newScore->getScore()) {
                //le nouveau score est meilleur
                if ($score != null) {
                    $em->remove($score);
                    $em->flush();
                }
                $em->persist($newScore);
            } else {
                $score->setSession($newScore->getSession());
            }
            $user->setCredits($user->getCredits() + 1);
            $em->flush();

            //calculation of the ponderate PP scores
            if ($score->isRankable()) {
                $totalPondPPScore = $rankingScoreService->calculateTotalPondPPScore($user);
                //insert/update of the score into ranked_scores
                $rankedScore = $rankedScoresRepository->findOneBy([
                    'user' => $user
                ]);

                if ($rankedScore == null) {
                    $rankedScore = new RankedScores();
                    $rankedScore->setUser($user);
                    $em->persist($rankedScore);
                }
                $rankedScore->setTotalPPScore($totalPondPPScore);
            }
            $em->flush();
            $histories = $scoreHistoryRepository
                ->createQueryBuilder('s')
                ->where('s.user = :user')
                ->setParameter('user', $user)
                ->andWhere('s.songDifficulty = :songDifficulty')
                ->setParameter('songDifficulty', $songDiff)
                ->getQuery()
                ->getResult();

            foreach ($histories as $history) {
                $history->setSession($newScore->getSession());
                $em->flush();
            }

            return new JsonResponse([
                "rank" => $scoreService->getTheoricalRank($songDiff, $newScore->getScore()),
                "score" => $newScore->getScore(),
                "ranking" => $scoreService->getTop5Wanadev($songDiff, $user)
            ], 200, ["content-type" => "application/json"]);
        }

        return new JsonResponse($scoreService->getTop5Wanadev($songDiff, $user), 200, [
            "content-type" => "application/json",
            "my-custom-key" => "abcdefghijklmnop"
        ]);
    }

    #[Route(path: '/wanapi/score/{apiKey}/{osef}-{hash}/{oseftoo}/{oseftootoo}/board', name: 'wd_api_score_get_new', methods: [
        'GET',
        'POST'
    ])]
    public function scoreboard(
        Request $request,
        ManagerRegistry $doctrine,
        string $apiKey,
        string $hash,
        SongDifficultyRepository $songDifficultyRepository,
        UtilisateurRepository $utilisateurRepository,
        RankingScoreService $rankingScoreService,
        ScoreService $scoreService,
        RankedScoresRepository $rankedScoresRepository,
        ScoreRepository $scoreRepository,
        ScoreHistoryRepository $scoreHistoryRepository
    ): Response {
        /** @var Utilisateur $user */
        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
        if ($user == null) {
            return new JsonResponse("NOK USER", 400);
        }
        configureScope(function (Scope $scope) use ($user): void {
            $scope->setUser(['username' => $user->getUsername()]);
        });
        /** @var SongDifficulty $songDiff */
        $songDiff = $songDifficultyRepository->findOneBy(['wanadevHash' => $hash]);
        if ($songDiff == null) {
            return new JsonResponse("NOK DIFF", 400);
        }

        if ($request->isMethod("post")) {
            $data = json_decode($request->getContent(), true);
            $em = $doctrine->getManager();

            $newScore = new Score();
            $newScore->setUser($user);
            $newScore->setSongDifficulty($songDiff);
            $newScore->setScore($data['score']);
//            $newScore->setDateRagnarock($data['created_at']);
            $newScore->setSession($data['session']);
            $newScore->setCountry($data['country']);
            $newScore->setUserRagnarock($data['user']);
            $newScore->setPlateform($data['platform'] ?? "");
            $newScore->setComboBlue($data['stats']['ComboBlue']);
            $newScore->setComboYellow($data['stats']['ComboYellow']);
            $newScore->setHit($data['stats']['Hit']);
            $newScore->setHitDeltaAverage($data['stats']['HitDeltaAverage']);
            $newScore->setHitPercentage($data['stats']['HitPercentage']);
            $newScore->setMissed($data['stats']['Missed']);
            $newScore->setExtra(json_encode($data['extra']));
            $newScore->setPercentageOfPerfects($data['stats']['PercentageOfPerfects']);
            if ($newScore->isRankable()) {
                $rawPP = $rankingScoreService->calculateRawPP($newScore);
                $newScore->setRawPP($rawPP);
            }

            /** @var Score $score */
            $scoreQb = $scoreRepository
                ->createQueryBuilder('s')
                ->where('s.user = :user')
                ->setParameter('user', $user)
                ->andWhere('s.songDifficulty = :songDifficulty')
                ->setParameter('songDifficulty', $songDiff)
                ->setParameter('plateform', 'Steam_Flat');

            if (in_array($newScore->getPlateform(), ['Steam_Flat'])) {
                $scoreQb->andWhere('s.plateform = :plateform');
            } else {
                $scoreQb->andWhere('s.plateform != :plateform');
            }

            $score = $scoreQb->getQuery()->getOneOrNullResult();
            $rankingScoreService->archive($newScore);

            if ($score == null || $score->getScore() <= $newScore->getScore()) {
                //le nouveau score est meilleur
                if ($score != null) {
                    $em->remove($score);
                    $em->flush();
                }
                $em->persist($newScore);
            } else {
                $score->setSession($newScore->getSession());
            }
            $user->setCredits($user->getCredits() + 1);
            $em->flush();

            //calculation of the ponderate PP scores
            if ($score->isRankable()) {
                $totalPondPPScore = $rankingScoreService->calculateTotalPondPPScore($user);
                //insert/update of the score into ranked_scores
                $rankedScore = $rankedScoresRepository->findOneBy(['user' => $user]);

                if ($rankedScore == null) {
                    $rankedScore = new RankedScores();
                    $rankedScore->setUser($user);
                    $em->persist($rankedScore);
                }

                $rankedScore->setTotalPPScore($totalPondPPScore);
            }

            $em->flush();
            $histories = $scoreHistoryRepository
                ->createQueryBuilder('s')
                ->where('s.user = :user')
                ->setParameter('user', $user)
                ->andWhere('s.songDifficulty = :songDifficulty')
                ->setParameter('songDifficulty', $songDiff)
                ->getQuery()->getResult();

            foreach ($histories as $history) {
                $history->setSession($newScore->getSession());
                $em->flush();
            }

            return new JsonResponse(
                [
                    "rank" => $scoreService->getTheoricalRank($songDiff, $newScore->getScore()),
                    "score" => $newScore->getScore(),
                    "ranking" => $scoreService->getTop5Wanadev($songDiff, $user)
                ],
                200,
                ["content-type" => "application/json"]
            );
        }

        return new JsonResponse(
            $scoreService->getTop5Wanadev($songDiff, $user),
            200,
            [
                "content-type" => "application/json",
                "my-custom-key" => "abcdefghijklmnop"
            ]
        );
    }
}
