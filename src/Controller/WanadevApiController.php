<?php

namespace App\Controller;

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
    const VR_PLATEFORM = ['Steam', 'Viveport', 'Oculus', 'Pico'];

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
        ScoreHistoryRepository $scoreHistoryRepository
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
            $scoreHistoryRepository
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
        ScoreHistoryRepository $scoreHistoryRepository
    ): Response {
        /** @var Utilisateur $user */
        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);

        if ($user == null) {
            return new JsonResponse('NOK USER', 400);
        }

        configureScope(function (Scope $scope) use ($user): void {
            $scope->setUser(['username' => $user->getUsername()]);
        });

        $songDiff = $songDifficultyRepository->findOneBy(['wanadevHash' => $hash]);

        if ($songDiff == null) {
            return new JsonResponse('NOK DIFF', 400);
        }

        $data = json_decode($request->getContent(), true);
        $plateform = $data['platform'] ?? 'Steam';
        $isVR = in_array($plateform, self::VR_PLATEFORM);

        if ($request->isMethod('post')) {
            $em = $doctrine->getManager();
            $newScore = $this->setNewScoreWithData($user, $songDiff, $data);

            if ($newScore->isRankable()) {
                $rawPP = $rankingScoreService->calculateRawPP($newScore);
                $newScore->setRawPP($rawPP);
            }

            $score = $scoreRepository->getOneByUserDiffVrOrNot($user, $songDiff, $isVR);
            $scoreService->archive($newScore);
            $user->setCredits($user->getCredits() + 1);
            $utilisateurRepository->add($user);

            if ($score && $score->getScore() <= $newScore->getScore()) {
                //le nouveau score est meilleur
                $scoreRepository->remove($score);
                $scoreRepository->add($newScore);
            }

            //calculation of the ponderate PP scores
            if ($newScore->isRankable()) {
                $rankingScoreService->calculateTotalPondPPScore($user, $isVR);
            }

            $scoreService->updateSessions($user, $songDiff, $isVR, $newScore->getSession());

            return new JsonResponse(
                [
                    'rank' => $scoreService->getTheoricalRank($songDiff, $newScore->getScore()),
                    'score' => $newScore->getScore(),
                    'ranking' => $scoreService->getTop5Wanadev($songDiff, $user, $isVR)
                ],
                200,
                ['content-type' => 'application/json']
            );
        }

        return new JsonResponse(
            $scoreService->getTop5Wanadev($songDiff, $user, $isVR),
            200,
            [
                'content-type' => 'application/json',
                'my-custom-key' => 'abcdefghijklmnop'
            ]
        );
    }

    private function setNewScoreWithData(Utilisateur $user, SongDifficulty $songDiff, mixed $data): Score
    {
        $plateform = $data['platform'] ?? 'Steam';
        $newScore = new Score();
        $newScore->setUser($user);
        $newScore->setSongDifficulty($songDiff);
        $newScore->setScore($data['score']);
        $newScore->setSession($data['session']);
        $newScore->setCountry($data['country']);
        $newScore->setUserRagnarock($data['user']);
        $newScore->setPlateform($plateform);
        $newScore->setComboBlue($data['stats']['ComboBlue']);
        $newScore->setComboYellow($data['stats']['ComboYellow']);
        $newScore->setHit($data['stats']['Hit']);
        $newScore->setHitDeltaAverage($data['stats']['HitDeltaAverage']);
        $newScore->setHitPercentage($data['stats']['HitPercentage']);
        $newScore->setMissed($data['stats']['Missed']);
        $newScore->setExtra(json_encode($data['extra']));
        $newScore->setPercentageOfPerfects($data['stats']['PercentageOfPerfects']);
        return $newScore;
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
            $scoreHistoryRepository
        );
    }
}
