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
    const VR_PLATEFORM = ['Steam', 'Viveport', 'Oculus', 'Pico', 'PS5'];
    #[Route(path: '/wanapi/score/{apiKey}/{osef}-{hash}/search', name: 'wd_api_score_search_friends', methods: ['GET', 'POST'])]
    public function searchFriend(
        Request $request,
        string $apiKey,
        string $hash,
        SongDifficultyRepository $songDifficultyRepository,
        UtilisateurRepository $utilisateurRepository,
        RankingScoreService $rankingScoreService,
        ScoreService $scoreService,
        ScoreRepository $scoreRepository,
    ): Response {
        return new JsonResponse([
                [
                    "platform" => "Steam",
                    "user" => "001",
                    "score" => 2,
                    "created_at" => null,
                    "session" => "RagnarockSession0",
                    "pseudo" => "Not Available now",
                    "country" => "en",
                    "stats" => [
                        "ComboBlue" => 0,
                        "ComboYellow" => 0,
                        "Hit" => 0,
                        "HitDeltaAverage" => 0,
                        "HitPercentage" => 0,
                        "Missed" => 0,
                        "PercentageOfPerfects" => 0
                    ],
                    "rank" => 1
                ],
            [
                "platform" => "Steam",
                "user" => "002",
                "score" => 1,
                "created_at" => null,
                "session" => "RagnarockSession02",
                "pseudo" => "but soon we hope",
                "country" => "en",
                "stats" => [
                    "ComboBlue" => 0,
                    "ComboYellow" => 0,
                    "Hit" => 0,
                    "HitDeltaAverage" => 0,
                    "HitPercentage" => 0,
                    "Missed" => 0,
                    "PercentageOfPerfects" => 0
                ],
                "rank" => 2
            ],

        ],
            200,
            ['content-type' => 'application/json']);
    }

    #[Route(path: '/wanapi/score/{apiKey}/{osef}-{hash}', name: 'wd_api_score_simple_get', methods: ['GET', 'POST'])]
    public function scoreSimple(
        Request $request,
        string $apiKey,
        string $hash,
        SongDifficultyRepository $songDifficultyRepository,
        UtilisateurRepository $utilisateurRepository,
        RankingScoreService $rankingScoreService,
        ScoreService $scoreService,
        ScoreRepository $scoreRepository,
    ): Response {
        return $this->score(
            $request,
            $apiKey,
            $hash,
            null,
            $songDifficultyRepository,
            $utilisateurRepository,
            $rankingScoreService,
            $scoreService,
            $scoreRepository,
        );
    }

    #[Route(path: '/wanapi/score/{apiKey}/{osef}-{hash}/{currentPlateform}/{oseftootoo}', name: 'wd_api_score_get', methods: [
        'GET',
        'POST'
    ])]
    public function score(
        Request $request,
        string $apiKey,
        string $hash,
        ?string $currentPlateform,
        SongDifficultyRepository $songDifficultyRepository,
        UtilisateurRepository $utilisateurRepository,
        RankingScoreService $rankingScoreService,
        ScoreService $scoreService,
        ScoreRepository $scoreRepository,
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
        $plateform = $data['platform'] ?? $currentPlateform ?? 'Steam';
        $isVr = in_array($plateform, self::VR_PLATEFORM);

        if ($currentPlateform) {
            $returnArray = [$currentPlateform, ... explode('|', trim($request->query->get('platform'),'|'))];
        } else {
            $returnArray = explode('|', trim($request->query->get('platform'),'|'));
        }

        if ($request->isMethod('post')) {
            $newScore = $this->setNewScoreWithData($user, $songDiff, $data);

            if ($newScore->isRankable()) {
                $rawPP = $rankingScoreService->calculateRawPP($newScore);
                $newScore->setRawPP($rawPP);
            }

            $score = $scoreRepository->getOneByUserDiffVrOrNot($user, $songDiff, $isVr);
            $scoreService->archive($newScore);
            $user->setCredits($user->getCredits() + 1);
            $utilisateurRepository->add($user);

            if ($score == null || $score->getScore() <= $newScore->getScore()) {
                //le nouveau score est meilleur
                if($score) {
                    $scoreRepository->remove($score);
                }

                $scoreRepository->add($newScore);
            }

            //calculation of the ponderate PP scores
            if ($newScore->isRankable()) {
                $rankingScoreService->calculateTotalPondPPScore($user, $isVr);
            }

            $scoreService->updateSessions($user, $songDiff, $isVr, $newScore->getSession());

            return new JsonResponse(
                [
                    'rank' => $scoreService->getTheoricalRank($songDiff, $newScore->getScore(), $returnArray),
                    'score' => $newScore->getScore(),
                    'ranking' => $scoreService->getTop5Wanadev($songDiff, $user, $returnArray, $isVr)
                ],
                200,
                ['content-type' => 'application/json']
            );
        }

        return new JsonResponse(
            $scoreService->getTop5Wanadev($songDiff, $user, $returnArray, $isVr),
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

    #[Route(path: '/wanapi/score/{apiKey}/{osef}-{hash}/{currentPlateform}/{oseftootoo}/board', name: 'wd_api_score_get_new', methods: [
        'GET',
        'POST'
    ])]
    public function scoreboard(
        Request $request,
        string $apiKey,
        string $hash,
        string $currentPlateform,
        SongDifficultyRepository $songDifficultyRepository,
        UtilisateurRepository $utilisateurRepository,
        RankingScoreService $rankingScoreService,
        ScoreService $scoreService,
        ScoreRepository $scoreRepository,
    ): Response {
        return $this->score(
            $request,
            $apiKey,
            $hash,
            $currentPlateform,
            $songDifficultyRepository,
            $utilisateurRepository,
            $rankingScoreService,
            $scoreService,
            $scoreRepository,
        );
    }
}
