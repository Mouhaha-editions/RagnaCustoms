<?php

namespace App\Controller;

use App\Entity\Overlay;
use App\Entity\RankedScores;
use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use App\Repository\DifficultyRankRepository;
use App\Repository\OverlayRepository;
use App\Repository\RankedScoresRepository;
use App\Repository\ScoreHistoryRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongCategoryRepository;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Repository\UtilisateurRepository;
use App\Service\ScoreService;
use App\Service\SongService;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Sentry\State\Scope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Sentry\configureScope;


class WanadevApiController extends AbstractController
{


    /**
     * @Route("/wanapi/score/{apiKey}/{osef}-{hash}/{oseftoo}/{oseftootoo}", name="wd_api_score_get",methods={"GET"})
     */
    public function score(Request $request, string $apiKey, string $hash, SongDifficultyRepository $songDifficultyRepository, UtilisateurRepository $utilisateurRepository,  ScoreService $scoreService, LoggerInterface $logger, $onlyMe = true): Response
    {
        /** @var Utilisateur $user */
        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
        if ($user == null) {

            $logger->error("API : " . $apiKey . " USER NOT FOUND");
            return new JsonResponse("NOK USER", 400);
        }

        configureScope(function (Scope $scope) use ($user): void {
            $scope->setUser(['username' => $user->getUsername()]);
        });
        /** @var SongDifficulty $songDiff */
        $songDiff = $songDifficultyRepository->findOneBy(['wanadevHash' => $hash]);
        if($songDiff == null){
            return new JsonResponse("NOK DIFF", 400);
        }
//if(!$onlyMe) {
    return new JsonResponse($scoreService->getTop5Wanadev($songDiff,$user), 200);
//}else{
//    return new JsonResponse([$scoreService->getMyScoreWanadev($songDiff, $user)], 200);
//}
        //        [
//            "rank" => $scoreService->getLeaderboardPosition($user, $songDiff,'0'),
//            "ranking" => $scoreService->getTop5Wanadev($songDiff)
//        ]
    }
    /**
     * @Route("/wanapi/score/{apiKey}/{osef}-{hash}", name="wd_api_score_simple_get",methods={"GET"})
     */
    public function scoreSimple(Request $request, string $apiKey, string $hash, SongDifficultyRepository $songDifficultyRepository, UtilisateurRepository $utilisateurRepository, ScoreService $scoreService, LoggerInterface $logger): Response
    {
        return $this->score($request, $apiKey,$hash,$songDifficultyRepository,$utilisateurRepository,$scoreService, $logger, false);
    }

//    /**
//     * @Route("/wd-api/score/{apiKey}", name="wd_api_score_post",methods={"POST"})
//     */
//    public function scorePost(Request $request, string $apiKey, DifficultyRankRepository $difficultyRankRepository, SongDifficultyRepository $songDifficultyRepository, ScoreRepository $scoreRepository, ScoreHistoryRepository $scoreHistoryRepository, UtilisateurRepository $utilisateurRepository, SongRepository $songRepository, SongService $songService, ScoreService $scoreService, RankedScoresRepository $rankedScoresRepository, LoggerInterface $logger): Response
//    {
//        $em = $this->getDoctrine()->getManager();
//        $results = [];
//        $ranked = false;
//        $data = json_decode($request->getContent(), true);
//
//        /** @var Utilisateur $user */
//        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
//        if ($user == null) {
//
//            $logger->error("API : " . $apiKey . " USER NOT FOUND");
//            return new JsonResponse("NOK USER", 400);
//        }
//
//        configureScope(function (Scope $scope) use ($user): void {
//            $scope->setUser(['username' => $user->getUsername()]);
//        });
//
//
//        try {
//            $song = $songRepository->findOneBy(['newGuid' => $hash]);
//            if ($song == null) {
//                $results[] = [
//                    "hash" => $hash,
//                    "level" => $level,
//                    "message" => "Score not saved (song not found) ",
//                    "ranked" => $ranked,
//                    "success" => false,
//                    "error" => "1_SONG_NOT_FOUND"
//                ];
//                $logger->error("API : " . $apiKey . " " . $hash . " 1_SONG_NOT_FOUND");
//                return new JsonResponse($results, 400);
//            }
//            $rank = $difficultyRankRepository->findOneBy(['level' => $level]);
//            $songDiff = $songDifficultyRepository->findOneBy([
//                'song' => $song,
//                "difficultyRank" => $rank
//            ]);
//
//            if ($songDiff == null) {
//                $results[] = [
//                    "hash" => $hash,
//                    "level" => $level,
//                    "ranked" => $ranked,
//                    "message" => "Score not saved (level not found) ",
//                    "success" => false,
//                    "error" => "2_LEVEL_NOT_FOUND"
//                ];
//                $logger->error("API : " . $apiKey . " " . $hash . " " . $level . " 2_LEVEL_NOT_FOUND");
//                return new JsonResponse($results, 400);
//            }
//            if ($songDiff->getTheoricalMaxScore() <= 0) {
//                $songDiff->setTheoricalMaxScore($songService->calculateTheoricalMaxScore($songDiff));
//            }
//
//            $score = $scoreRepository->findOneBy([
//                'user' => $user,
//                'difficulty' => $level,
//                'hash' => $hash,
//                'season' => null
//            ]);
//            $scoreData = round(floatval($data['Score']) / 100, 2);
//
//            if ($score == null) {
//                $score = new Score();
//                $score->setUser($user);
//                $score->setScore($scoreData);
//                $score->setDifficulty($level);
//                $score->setSong($song);
//                $score->setSongDifficulty($songDiff);
//                $score->setHash($hash);
//                $score->setPercentage($data["Percentage"] ?? null);
//                $score->setPercentage2($data["Percentage2"] ?? null);
//                $score->setCombos($data["Combos"] ?? null);
//                $score->setNotesHit($data["NotesHit"] ?? null);
//                $score->setNotesMissed($data["NotesMissed"] ?? null);
//                $score->setNotesNotProcessed($data["NotesNotProcessed"] ?? null);
//                $score->setHitAccuracy($data["HitAccuracy"] ?? null);
//                $score->setHitSpeed($data["HitSpeed"] ?? null);
//                if ($songDiff->isRanked()) {
//                    $rawPP = $this->calculateRawPP($score, $songDiff);
//                    $score->setRawPP($rawPP);
//                }
//                $em->persist($score);
//            }
//
//            $oldscore = $score->getScore();
//            if ($score->getScore() <= $scoreData) {
//                $score->setScore($scoreData);
//                $score->setPercentage($data["Percentage"] ?? null);
//                $score->setPercentage2($data["Percentage2"] ?? null);
//                $score->setCombos($data["Combos"] ?? null);
//                $score->setNotesHit($data["NotesHit"] ?? null);
//                $score->setNotesMissed($data["NotesMissed"] ?? null);
//                $score->setNotesNotProcessed($data["NotesNotProcessed"] ?? null);
//                $score->setHitAccuracy($data["HitAccuracy"] ?? null);
//                $score->setHitSpeed($data["HitSpeed"] ?? null);
//                if ($score->getScore() >= 99000) {
//                    $score->setScore($score->getScore() / 1000000);
//                }
//                if ($songDiff->isRanked()) {
//                    $rawPP = $this->calculateRawPP($score, $songDiff);
//                    $score->setRawPP($rawPP);
//                }
//
//                $em->flush();
//
//                $results[] = [
//                    "hash" => $hash,
//                    "level" => $level,
//                    "success" => true,
//                    "ranked" => $ranked,
//                    "message" => "Score saved (old score : " . $oldscore . " < new score : " . $scoreData . ") ",
//                    "error" => "SUCCESS"
//                ];
//            } else {
//                $em->flush();
//                $results[] = [
//                    "hash" => $hash,
//                    "level" => $level,
//                    "success" => true,
//                    "ranked" => $ranked,
//                    "message" => "Score not saved (old score : " . $oldscore . " >= new score : " . $scoreData . ")",
//                    "error" => "SUCCESS"
//                ];
//            }
//            $scoreService->archive($score);
//
//            $results[] = [
//                "hash" => $hash,
//                "level" => $level,
//                "success" => true,
//                "message" => "Score saved",
//                "error" => "SUCCESS"
//            ];
//        } catch (Exception $e) {
//            $results[] = [
//                "hash" => $hash,
//                "level" => $level,
//                "success" => false,
//                "error" => "3_SCORE_NOT_SAVED",
//                "message" => "Score not saved because of an unexpected error",
//                'detail' => $e->getMessage()
//            ];
//            $logger->error("API : " . $apiKey . " " . $hash . " " . $data["Level"] . " 3_SCORE_NOT_SAVED : " . $e->getMessage() . " ");
//            return new JsonResponse($results, 400);
//        }
//
//        //calculation of the ponderate PP scores
//        if ($songDiff->isRanked()) {
//            $totalPondPPScore = $this->calculateTotalPondPPScore($scoreRepository, $user);
//
//            //insert/update of the score into ranked_scores
//            $rankedScore = $rankedScoresRepository->findOneBy([
//                'user' => $user
//            ]);
//
//            if ($rankedScore == null) {
//                $logger->error("null");
//            } else {
//                $logger->error("ID : " . $rankedScore->getId() . " / USER : " . $rankedScore->getUser()->getId());
//            }
//
//            if ($rankedScore == null) {
//                $rankedScore = new RankedScores();
//                $rankedScore->setUser($user);
//                $rankedScore->setTotalPPScore($totalPondPPScore);
//                $em->persist($rankedScore);
//            }
//            $rankedScore->setTotalPPScore($totalPondPPScore);
//            $em->flush();
//        }
//
//        return new JsonResponse($results, 200);
//    }

    private function calculateRawPP(Score $score, SongDifficulty $songDiff)
    {
        $userScore = $score->getScore();
        $songLevel = $score->getDifficulty();
        $maxSongScore = $songDiff->getTheoricalMaxScore();
        // raw pp is calculated by making the ratio between the current score and the theoretical maximum score.
        // it is ponderated by the song level
        $rawPP = (($userScore / $maxSongScore) * (0.4 + 0.1 * $songLevel)) * 100;

        return round($rawPP, 2);
    }

    private function calculateTotalPondPPScore(ScoreRepository $scoreRepository, Utilisateur $user)
    {
        $totalPP = 0;
        $scores = $scoreRepository->createQueryBuilder('score')->leftJoin('score.songDifficulty', 'diff')->where('score.user = :user')->andWhere('diff.isRanked = true')->setParameter('user', $user)->addOrderBy('score.rawPP', 'desc')->getQuery()->getResult();

        $index = 0;
        foreach ($scores as $score) {
            $rawPPScore = $score->getRawPP();
            $pondPPScore = $rawPPScore * pow(0.965, $index);
            $totalPP = $totalPP + $pondPPScore;
            $index++;
        }

        return round($totalPP, 2);
    }


}
