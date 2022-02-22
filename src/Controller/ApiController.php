<?php

namespace App\Controller;

use App\Entity\Overlay;
use App\Entity\RankedScores;
use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use App\Enum\EGamification;
use App\Repository\DifficultyRankRepository;
use App\Repository\OverlayRepository;
use App\Repository\RankedScoresRepository;
use App\Repository\ScoreHistoryRepository;
use App\Repository\ScoreRepository;
use App\Repository\SeasonRepository;
use App\Repository\SongCategoryRepository;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Repository\UtilisateurRepository;
use App\Service\GamificationService;
use App\Service\SongService;
use App\Service\StatisticService;
use ContainerCexz9GN\getMaker_AutoCommand_MakeMessengerMiddlewareService;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Psr\Log\LoggerInterface;
use Sentry\State\Scope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Sentry\configureScope;


class ApiController extends AbstractController
{

    const CurrentVersion = "1.6.0";


    /**
     * @Route("/api/song/check-updates", name="api_song_check_updates")
     */
    public function checkUpdates(Request $request, SongRepository $songRepository): Response
    {

        /**
         *  "Id" => $song->getId(),
         * "Name" => $song->getName(),
         * "Author" => $song->getAuthorName(),
         * "IsSeasonRanked" => $song->isSeasonRanked(),
         * "Hash" => $song->getNewGuid(),
         * "Mapper" => $song->getLevelAuthorName(),
         * "Difficulties" => $song->getSongDifficultiesStr(),
         */
        $songs = $songRepository->createQueryBuilder('s')
            ->select('s.id, s.name, s.authorName AS author, s.levelAuthorName AS mapper, s.newGuid AS hash, GROUP_CONCAT(r.level) AS Difficulties')
            ->leftJoin("s.songDifficulties", 'sd')
            ->leftJoin('sd.difficultyRank', 'r')
            ->where("s.isDeleted != 1")
            ->groupBy('s.id')
            ->getQuery()
            ->getArrayResult();

        return new JsonResponse($songs);
    }


    /**
     * @Route("/api/score/v2", name="api_score_v2")
     * @param Request $request
     * @param StatisticService $statisticService
     * @param GamificationService $gamificationService
     * @param SeasonRepository $seasonRepository
     * @param DifficultyRankRepository $difficultyRankRepository
     * @param SongDifficultyRepository $songDifficultyRepository
     * @param ScoreRepository $scoreRepository
     * @param ScoreHistoryRepository $scoreHistoryRepository
     * @param UtilisateurRepository $utilisateurRepository
     * @param SongRepository $songRepository
     * @param LoggerInterface $logger
     * @return Response
     * @throws NonUniqueResultException
     */
    public function scoreV2(Request                  $request,
                            StatisticService         $statisticService,
                            GamificationService      $gamificationService,
                            SeasonRepository         $seasonRepository,
                            DifficultyRankRepository $difficultyRankRepository,
                            SongDifficultyRepository $songDifficultyRepository,
                            ScoreRepository          $scoreRepository,
                            ScoreHistoryRepository   $scoreHistoryRepository,
                            UtilisateurRepository    $utilisateurRepository,
                            SongRepository           $songRepository,
                            SongService              $songService,
                            RankedScoresRepository   $rankedScoresRepository,
                            LoggerInterface          $logger): Response
    {
        $em = $this->getDoctrine()->getManager();
        $results = [];
        $apiKey = $request->headers->get('x-api-key');
        $ranked = false;

        $data = json_decode($request->getContent(), true);

        if ($data == null) {
            $logger->error("no data");
            $results[] = [
                "user" => $apiKey,
                "hash" => "all",
                "ranked" => $ranked,
                "level" => "",
                "message" => "Score not saved (no data) ",
                "success" => false,
                "error" => "0_NO_CONTENT"
            ];
            return new JsonResponse($results, 500);
        }

        /** @var Utilisateur $user */
        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
        if ($user == null) {
            $results[] = [
                "user" => $apiKey,
                "hash" => "all",
                "level" => "",
                "ranked" => $ranked,
                "message" => "Score not saved (user not found) ",
                "success" => false,
                "error" => "0_USER_NOT_FOUND"
            ];
            $logger->error("API : " . $apiKey . " USER NOT FOUND");
            return new JsonResponse($results, 400);
        }

        configureScope(function (Scope $scope) use ($user): void {
            $scope->setUser(['username' => $user->getUsername()]);
        });

        if ($data["AppVersion"] < self::CurrentVersion) {
            $results[] = [
                "user" => $apiKey,
                "hash" => "all",
                "ranked" => $ranked,
                "level" => "",
                "message" => "Score not saved (wrong app version, need : " . (self::CurrentVersion) . " get at least " . $data["AppVersion"] . " )",
                "success" => false,
                "error" => "0_WRONG_APP"
            ];
        }
        $hash = $data["HashInfo"];
        $level = $data["Level"];

        try {
            $song = $songRepository->findOneBy(['newGuid' => $hash]);
            if ($song == null) {
                $results[] = [
                    "hash" => $hash,
                    "level" => $level,
                    "message" => "Score not saved (song not found) ",
                    "ranked" => $ranked,
                    "success" => false,
                    "error" => "1_SONG_NOT_FOUND"
                ];
                $logger->error("API : " . $apiKey . " " . $hash . " 1_SONG_NOT_FOUND");
                return new JsonResponse($results, 400);
            }
            $rank = $difficultyRankRepository->findOneBy(['level' => $level]);
            $songDiff = $songDifficultyRepository->findOneBy([
                'song' => $song,
                "difficultyRank" => $rank
            ]);

            if ($songDiff == null) {
                $results[] = [
                    "hash" => $hash,
                    "level" => $level,
                    "ranked" => $ranked,
                    "message" => "Score not saved (level not found) ",
                    "success" => false,
                    "error" => "2_LEVEL_NOT_FOUND"
                ];
                $logger->error("API : " . $apiKey . " " . $hash . " " . $level . " 2_LEVEL_NOT_FOUND");
                return new JsonResponse($results, 400);
            }
            if ($songDiff->getTheoricalMaxScore() <= 0) {
                $songDiff->setTheoricalMaxScore($songService->calculateTheoricalMaxScore($songDiff));
            }

            $score = $scoreRepository->findOneBy([
                'user' => $user,
                'difficulty' => $level,
                'hash' => $hash,
                'season' => null
            ]);
            $scoreData = round(floatval($data['Score']) / 100, 2);

            if ($score == null) {
                $score = new Score();
                $score->setUser($user);
                $score->setScore($scoreData);
                $score->setDifficulty($level);
                $score->setSong($song);
                $score->setSongDifficulty($songDiff);
                $score->setHash($hash);
                $score->setPercentage($data["Percentage"] ?? null);
                $score->setPercentage2($data["Percentage2"] ?? null);
                $score->setCombos($data["Combos"] ?? null);
                $score->setNotesHit($data["NotesHit"] ?? null);
                $score->setNotesMissed($data["NotesMissed"] ?? null);
                $score->setNotesNotProcessed($data["NotesNotProcessed"] ?? null);
                $score->setHitAccuracy($data["HitAccuracy"] ?? null);
                $score->setHitSpeed($data["HitSpeed"] ?? null);
                if ($songDiff->isRanked()) {
                    $rawPP = $this->calculateRawPP($score, $songDiff);
                    $score->setRawPP($rawPP);
                }
                $em->persist($score);
            }

            $scoreHistory = $scoreHistoryRepository->findOneBy([
                'user' => $user,
                'difficulty' => $level,
                'hash' => $hash,
                "score" => $scoreData
            ]);
            $oldscore = $score->getScore();
            if ($scoreHistory == null) {
                $scoreHistory = new ScoreHistory();
                $scoreHistory->setUser($user);
                $scoreHistory->setDifficulty($level);
                $scoreHistory->setSong($song);
                $scoreHistory->setSongDifficulty($songDiff);
                $scoreHistory->setHash($hash);
                $scoreHistory->setScore($scoreData);
                $em->persist($scoreHistory);
            }
            $scoreHistory->setPercentage($data["Percentage"] ?? null);
            $scoreHistory->setPercentage2($data["Percentage2"] ?? null);
            $scoreHistory->setCombos($data["Combos"] ?? null);
            $scoreHistory->setNotesHit($data["NotesHit"] ?? null);
            $scoreHistory->setNotesMissed($data["NotesMissed"] ?? null);
            $scoreHistory->setNotesNotProcessed($data["NotesNotProcessed"] ?? null);
            $scoreHistory->setHitAccuracy($data["HitAccuracy"] ?? null);
            $scoreHistory->setHitSpeed($data["HitSpeed"] ?? null);
            $em->flush();
            if ($score->getScore() <= $scoreData) {
                $score->setScore($scoreData);
                $score->setPercentage($data["Percentage"] ?? null);
                $score->setPercentage2($data["Percentage2"] ?? null);
                $score->setCombos($data["Combos"] ?? null);
                $score->setNotesHit($data["NotesHit"] ?? null);
                $score->setNotesMissed($data["NotesMissed"] ?? null);
                $score->setNotesNotProcessed($data["NotesNotProcessed"] ?? null);
                $score->setHitAccuracy($data["HitAccuracy"] ?? null);
                $score->setHitSpeed($data["HitSpeed"] ?? null);
                if ($score->getScore() >= 99000) {
                    $score->setScore($score->getScore() / 1000000);
                }
                if ($songDiff->isRanked()) {
                    $rawPP = $this->calculateRawPP($score, $songDiff);
                    $score->setRawPP($rawPP);
                }

                $em->flush();

                $results[] = [
                    "hash" => $hash,
                    "level" => $level,
                    "success" => true,
                    "ranked" => $ranked,
                    "message" => "Score saved (old score : " . $oldscore . " < new score : " . $scoreData . ") ",
                    "error" => "SUCCESS"
                ];
            } else {
                $em->flush();
                $results[] = [
                    "hash" => $hash,
                    "level" => $level,
                    "success" => true,
                    "ranked" => $ranked,
                    "message" => "Score not saved (old score : " . $oldscore . " >= new score : " . $scoreData . ")",
                    "error" => "SUCCESS"
                ];
            }

            $results[] = [
                "hash" => $hash,
                "level" => $level,
                "success" => true,
                "message" => "Score saved",
                "error" => "SUCCESS"
            ];
        } catch (Exception $e) {
            $results[] = [
                "hash" => $hash,
                "level" => $level,
                "success" => false,
                "error" => "3_SCORE_NOT_SAVED",
                "message" => "Score not saved because of an unexpected error",
                'detail' => $e->getMessage()
            ];
            $logger->error("API : " . $apiKey . " " . $hash . " " . $data["Level"] . " 3_SCORE_NOT_SAVED : " . $e->getMessage() . " ");
            return new JsonResponse($results,400);
        }

        //calculation of the ponderate PP scores
        if ($songDiff->isRanked()) {
            $totalPondPPScore = $this->calculateTotalPondPPScore($scoreRepository, $user);

            //insert/update of the score into ranked_scores
            $rankedScore = $rankedScoresRepository->findOneBy([
                'user' => $user
            ]);

            if ($rankedScore == null) {
                $logger->error("null");
            } else {
                $logger->error("ID : " . $rankedScore->getId() . " / USER : " . $rankedScore->getUser()->getId());
            }

            if ($rankedScore == null) {
                $rankedScore = new RankedScores();
                $rankedScore->setUser($user);
                $rankedScore->setTotalPPScore($totalPondPPScore);
                $em->persist($rankedScore);
            }
            $rankedScore->setTotalPPScore($totalPondPPScore);
            $em->flush();
        }

        return new JsonResponse($results, 200);
    }

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
        $scores = $scoreRepository->createQueryBuilder('score')
            ->leftJoin('score.SongDifficulty', 'diff')
            ->where('score.user = :user')
            ->andWhere('diff.isRanked = true')
            ->setParameter('user', $user)
            ->addOrderBy('score.rawPP', 'desc')
            ->getQuery()->getResult();

        $index = 0;
        foreach ($scores as $score) {
            $rawPPScore = $score->getRawPP();
            $pondPPScore = $rawPPScore * pow(0.965, $index);
            $totalPP = $totalPP + $pondPPScore;
            $index++;
        }

        return round($totalPP, 2);
    }

    /**
     * @Route("/api/score/v3", name="api_score_v3")
     * @param Request $request
     * @param StatisticService $statisticService
     * @param GamificationService $gamificationService
     * @param SeasonRepository $seasonRepository
     * @param DifficultyRankRepository $difficultyRankRepository
     * @param SongDifficultyRepository $songDifficultyRepository
     * @param ScoreRepository $scoreRepository
     * @param ScoreHistoryRepository $scoreHistoryRepository
     * @param UtilisateurRepository $utilisateurRepository
     * @param SongRepository $songRepository
     * @param LoggerInterface $logger
     * @return Response
     * @throws NonUniqueResultException
     */
    public function scoreV3(Request                  $request,
                            StatisticService         $statisticService,
                            GamificationService      $gamificationService,
                            SeasonRepository         $seasonRepository,
                            DifficultyRankRepository $difficultyRankRepository,
                            SongDifficultyRepository $songDifficultyRepository,
                            ScoreRepository          $scoreRepository,
                            ScoreHistoryRepository   $scoreHistoryRepository,
                            UtilisateurRepository    $utilisateurRepository,
                            SongRepository           $songRepository,
                            SongService              $songService,
                            LoggerInterface          $logger): Response
    {
        $em = $this->getDoctrine()->getManager();
        $results = [];
        $apiKey = $request->headers->get('x-api-key');
        $ranked = false;

        $data = json_decode($request->getContent(), true);

        if ($data == null) {
            $logger->error("no data");
            $results[] = [
                "user" => $apiKey,
                "hash" => "all",
                "ranked" => $ranked,
                "level" => "",
                "message" => "Score not saved (no data) ",
                "success" => false,
                "error" => "0_NO_CONTENT"
            ];
            return new JsonResponse($results, 500);
        }

        /** @var Utilisateur $user */
        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
        if ($user == null) {
            $results[] = [
                "user" => $apiKey,
                "hash" => "all",
                "level" => "",
                "ranked" => $ranked,
                "message" => "Score not saved (user not found) ",
                "success" => false,
                "error" => "0_USER_NOT_FOUND"
            ];
            $logger->error("API : " . $apiKey . " USER NOT FOUND");
            return new JsonResponse($results, 400);
        }
        configureScope(function (Scope $scope) use ($user): void {
            $scope->setUser(['username' => $user->getUsername()]);
        });

        $gamificationService->unlock(EGamification::ACHIEVEMENT_USE_API, $user);

        $season = $seasonRepository->createQueryBuilder('s')
            ->where('s.startDate <= :now')
            ->andWhere('s.endDate >= :now')
            ->setParameter('now', new DateTime())
            ->setFirstResult(0)->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();


        if ($data["AppVersion"] < self::CurrentVersion) {
            $results[] = [
                "user" => $apiKey,
                "hash" => "all",
                "ranked" => $ranked,
                "level" => "",
                "message" => "Score not saved (wrong app version, need : " . (self::CurrentVersion) . " get at least " . $data["AppVersion"] . " )",
                "success" => false,
                "error" => "0_WRONG_APP"
            ];
        }
        $hash = $data["HashInfo"];
        $level = $data["Level"];
        try {
            $song = $songRepository->findOneBy(['newGuid' => $hash]);
            if ($song == null) {
                $results[] = [
                    "hash" => $hash,
                    "level" => $level,
                    "message" => "Score not saved (song not found) ",
                    "ranked" => $ranked,
                    "success" => false,
                    "error" => "1_SONG_NOT_FOUND"
                ];
                $logger->error("API : " . $apiKey . " " . $hash . " 1_SONG_NOT_FOUND");
                return new JsonResponse($results, 400);
//                    return new JsonResponse($results,400);
            }
            $rank = $difficultyRankRepository->findOneBy(['level' => $level]);
            $songDiff = $songDifficultyRepository->findOneBy([
                'song' => $song,
                "difficultyRank" => $rank
            ]);

            if ($songDiff == null) {
                $results[] = [
                    "hash" => $hash,
                    "level" => $level,
                    "ranked" => $ranked,
                    "message" => "Score not saved (level not found) ",
                    "success" => false,
                    "error" => "2_LEVEL_NOT_FOUND"
                ];
                $logger->error("API : " . $apiKey . " " . $hash . " " . $level . " 2_LEVEL_NOT_FOUND");
                return new JsonResponse($results, 400);
            }

            if ($songDiff->getTheoricalMaxScore() <= 0) {
                $songDiff->setTheoricalMaxScore($songService->calculateTheoricalMaxScore($songDiff));
                $em->flush();
            }
            if ($season != null && $songDiff->isSeasonRanked()) {
                $score = $scoreRepository->findOneBy([
                    'user' => $user,
                    'difficulty' => $level,
                    'hash' => $hash,
                    'season' => $season
                ]);
                if ($score != null) {
                    $ranked = true;
                }
            } else {
                $score = $scoreRepository->findOneBy([
                    'user' => $user,
                    'difficulty' => $level,
                    'hash' => $hash,
                    'season' => null
                ]);
            }
            $scoreData = round(floatval($data['Score']) / 100, 2);

            if ($score == null) {
                $score = new Score();
                $score->setUser($user);
                $score->setScore($scoreData);
                $score->setDifficulty($level);
                $score->setHash($hash);
                $score->setPercentage($data["Percentage"] ?? null);
                $score->setPercentage2($data["Percentage2"] ?? null);
                $score->setCombos($data["Combos"] ?? null);
                $score->setNotesHit($data["NotesHit"] ?? null);
                $score->setNotesMissed($data["NotesMissed"] ?? null);
                $score->setNotesNotProcessed($data["NotesNotProcessed"] ?? null);
                $score->setHitAccuracy($data["HitAccuracy"] ?? null);
                $score->setHitSpeed($data["HitSpeed"] ?? null);
                if ($season != null && $songDiff->isSeasonRanked()) {
                    $score->setSeason($season);
                    $ranked = true;
                }
                $em->persist($score);
            }

            $scoreHistory = $scoreHistoryRepository->findOneBy([
                'user' => $user,
                'difficulty' => $level,
                'hash' => $hash,
                "score" => $scoreData
            ]);
            $oldscore = $score->getScore();
            if ($scoreHistory == null) {
                $scoreHistory = new ScoreHistory();
                $scoreHistory->setUser($user);
                $scoreHistory->setDifficulty($level);
                $scoreHistory->setHash($hash);
                $scoreHistory->setScore($scoreData);

                $em->persist($scoreHistory);
            }
            $scoreHistory->setPercentage($data["Percentage"] ?? null);
            $scoreHistory->setPercentage2($data["Percentage2"] ?? null);
            $scoreHistory->setCombos($data["Combos"] ?? null);
            $scoreHistory->setNotesHit($data["NotesHit"] ?? null);
            $scoreHistory->setNotesMissed($data["NotesMissed"] ?? null);
            $scoreHistory->setNotesNotProcessed($data["NotesNotProcessed"] ?? null);
            $scoreHistory->setHitAccuracy($data["HitAccuracy"] ?? null);
            $scoreHistory->setHitSpeed($data["HitSpeed"] ?? null);
            $em->flush();
            if ($score->getScore() <= $scoreData) {
                $score->setScore($scoreData);
                $score->setPercentage($data["Percentage"] ?? null);
                $score->setPercentage2($data["Percentage2"] ?? null);
                $score->setCombos($data["Combos"] ?? null);
                $score->setNotesHit($data["NotesHit"] ?? null);
                $score->setNotesMissed($data["NotesMissed"] ?? null);
                $score->setNotesNotProcessed($data["NotesNotProcessed"] ?? null);
                $score->setHitAccuracy($data["HitAccuracy"] ?? null);
                $score->setHitSpeed($data["HitSpeed"] ?? null);
                if ($score->getScore() >= 99000) {
                    $score->setScore($score->getScore() / 1000000);
                }
                $em->flush();
                $results[] = [
                    "hash" => $hash,
                    "level" => $level,
                    "success" => true,
                    "ranked" => $ranked,
                    "message" => "Score saved (old score : " . $oldscore . " < new score : " . $scoreData . ") ",
                    "error" => "SUCCESS"
                ];
            } else {
                $em->flush();
                $results[] = [
                    "hash" => $hash,
                    "level" => $level,
                    "success" => true,
                    "ranked" => $ranked,
                    "message" => "Score not saved (old score : " . $oldscore . " >= new score : " . $scoreData . ")",
                    "error" => "SUCCESS"
                ];
            }


            if ($song->getWip()) {
                $gamificationService->unlock(EGamification::ACHIEVEMENT_HELPER_LVL_1, $user);
                $gamificationService->add(EGamification::ACHIEVEMENT_HELPER_LVL_2, $user, 1, 10, $song->getId());
                $gamificationService->add(EGamification::ACHIEVEMENT_HELPER_LVL_3, $user, 1, 50, $song->getId());
            }

            $results[] = [
                "hash" => $hash,
                "level" => $level,
                "success" => true,
                "message" => "Score saved",
                "error" => "SUCCESS"
            ];
        } catch (Exception $e) {
            $results[] = [
                "hash" => $hash,
                "level" => $level,
                "success" => false,
                "error" => "3_SCORE_NOT_SAVED",
                "message" => "Score not saved because of an unexpected error",
                'detail' => $e->getMessage()
            ];
            $logger->error("API : " . $apiKey . " " . $hash . " " . $data["Level"] . " 3_SCORE_NOT_SAVED : " . $e->getMessage() . " ");

            return new JsonResponse($results, 400);

        }


        return new JsonResponse($results, 200);
    }

    /**
     * @Route("/api/search/{term}", name="api_search")
     */
    public function index(Request $request, string $term = null, SongRepository $songRepository): Response
    {
        $songsEntities = $songRepository->createQueryBuilder('s')
            ->where('(s.name LIKE :search_string OR s.authorName LIKE :search_string OR s.levelAuthorName LIKE :search_string)')
            ->andWhere('s.moderated = true')
            ->andWhere('s.isDeleted != true')
            ->setParameter('search_string', '%' . $term . '%')
            ->getQuery()->getResult();
        $songs = [];

        /** @var Song $song */
        foreach ($songsEntities as $song) {
            $songs[] = [
                "Id" => $song->getId(),
                "Name" => $song->getName(),
                "IsRanked" => $song->isSeasonRanked(),
                "Hash" => $song->getNewGuid(),
                "Author" => $song->getAuthorName(),
                "Mapper" => $song->getLevelAuthorName(),
                "Difficulties" => $song->getSongDifficultiesStr(),
                "CoverImageExtension" => $song->getCoverImageExtension(),
            ];
        }

        return new JsonResponse([
                "Results" => $songs,
                "Count" => count($songs)
            ]
        );
    }

    /**
     * @Route("/api/song/{id}", name="api_song")
     */
    public function song(Request $request, Song $song): Response
    {
        return new JsonResponse([
                "Id" => $song->getId(),
                "Name" => $song->getName(),
                "Author" => $song->getAuthorName(),
                "IsRanked" => $song->isSeasonRanked(),
                "Hash" => $song->getNewGuid(),
                "Mapper" => $song->getLevelAuthorName(),
                "Difficulties" => $song->getSongDifficultiesStr(),
                "CoverImageExtension" => $song->getCoverImageExtension(),
            ]
        );
    }

    /**
     * @Route("/api/hash/{hash}", name="api_hash")
     */
    public function hash(Request $request, string $hash, SongRepository $songRepository): Response
    {
        $song = $songRepository->createQueryBuilder('s')
            ->where('s.newGuid LIKE :search_string)')
            ->andWhere('s.moderated = true')
            ->setParameter('search_string', $hash)
            ->getQuery()->setFirstResult(0)->setMaxResults(1)->getOneOrNullResult();
        if (!$song) {
            return new Response("NOK", 400);
        }
        return new JsonResponse([
                "Id" => $song->getId(),
                "Name" => $song->getName(),
                "Author" => $song->getAuthorName(),
                "IsRanked" => $song->isSeasonRanked(),
                "Hash" => $song->getNewGuid(),
                "Mapper" => $song->getLevelAuthorName(),
                "Difficulties" => $song->getSongDifficultiesStr(),
                "CoverImageExtension" => $song->getCoverImageExtension(),
            ]
        );
    }

    //calculation of the user rawPP by song and level

    /**
     * @Route("/api/overlay/", name="api_hash")
     * @param Request $request
     * @param UtilisateurRepository $utilisateurRepository
     * @param DifficultyRankRepository $difficultyRankRepository
     * @param OverlayRepository $overlayRepository
     * @param SongRepository $songRepository
     * @param SongDifficultyRepository $songDifficultyRepository
     * @return Response
     */
    public function overlay(Request $request, UtilisateurRepository $utilisateurRepository, DifficultyRankRepository $difficultyRankRepository, OverlayRepository $overlayRepository, SongRepository $songRepository, SongDifficultyRepository $songDifficultyRepository): Response
    {
        $data = json_decode($request->getContent(), true);
        $apiKey = $request->headers->get('x-api-key');

        if ($data == null) {
            return new Response("NOK", 500);
        }

        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
        $em = $this->getDoctrine()->getManager();
        if ($user == null) {
            return new Response("NO USER", 500);
        }

        $overlay = $overlayRepository->findOneBy(["user" => $user]);
        if ($overlay == null) {
            $overlay = new Overlay();
            $overlay->setUser($user);
            $em->persist($overlay);
            $em->flush();
        }
        $song = $songRepository->findOneBy(['newGuid' => $data["HashInfo"]]);
        if ($song == null) {
            $overlay->setDifficulty(null);
            $overlay->setStartAt(null);
            $em->flush();
            return new Response("NOK", 500);
        }
        $rank = $difficultyRankRepository->findOneBy(['level' => $data['Level']]);
        $songDiff = $songDifficultyRepository->findOneBy([
            'song' => $song,
            "difficultyRank" => $rank
        ]);

        if ($songDiff == null) {
            $overlay->setDifficulty(null);
            $overlay->setStartAt(null);
            $em->flush();
            return new Response("NOK", 500);
        }

        $overlay->setDifficulty($songDiff);
        $overlay->setStartAt(new DateTime());
        $em->flush();

        return new Response("OK");
    }

    //Each song is ponderated by applying a coefficient dependent of the index of the score in the list.

    /**
     * @param Request $request
     * @Route("/api/song-categories", name="api_song_categories")
     */
    public function songCategories(Request $request, SongCategoryRepository $categoryRepository)
    {
        $data = $categoryRepository->createQueryBuilder("sc")
            ->select("sc.id AS id, sc.label AS text")->where('sc.label LIKE :search')
            ->setParameter('search', '%' . $request->get('q') . '%')
            ->andWhere('sc.isOnlyForAdmin = false')
            ->orderBy('sc.label')
            ->getQuery()->getArrayResult();

        return new JsonResponse([
            'results' => $data
        ]);
    }
}
