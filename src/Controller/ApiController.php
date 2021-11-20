<?php

namespace App\Controller;

use App\ApiModels\SessionModel;
use App\ApiModels\ResultModel;
use App\Entity\Gamification;
use App\Entity\Overlay;
use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Enum\EGamification;
use App\Repository\DifficultyRankRepository;
use App\Repository\OverlayRepository;
use App\Repository\ScoreHistoryRepository;
use App\Repository\ScoreRepository;
use App\Repository\SeasonRepository;
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
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;


use function Sentry\configureScope;

class ApiController extends AbstractController
{
    const CurrentVersion = "1.2.4";

    /**
     * @Route("/api/score/v2", name="api_score_v2")
     * @param Request $request
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
    public function scoreV2(Request $request,
                            StatisticService $statisticService,
                            GamificationService $gamificationService,
                            SeasonRepository $seasonRepository,
                            DifficultyRankRepository $difficultyRankRepository,
                            SongDifficultyRepository $songDifficultyRepository,
                            ScoreRepository $scoreRepository,
                            ScoreHistoryRepository $scoreHistoryRepository,
                            UtilisateurRepository $utilisateurRepository,
                            SongRepository $songRepository,
                            LoggerInterface $logger): Response
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
            return new JsonResponse($results,500);
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


        foreach ($data as $subScore) {
            if ($subScore["AppVersion"] < self::CurrentVersion) {
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
            $hash = $subScore["HashInfo"];
            $level = $subScore["Level"];
            try {
                $song = $songRepository->findOneByHash($hash);
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
                    continue;
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
                    continue;
                }

                if ($season != null && $songDiff->isRanked()) {
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
                $scoreData = round(floatval($subScore['Score']) / 100, 2);

                if ($score == null) {
                    $score = new Score();
                    $score->setUser($user);
                    $score->setScore($scoreData);
                    $score->setDifficulty($level);
                    $score->setHash($hash);
                    $score->setPercentage($subScore["Percentage"] ?? null);
                    $score->setPercentage2($subScore["Percentage2"] ?? null);
                    $score->setCombos($subScore["Combos"] ?? null);
                    $score->setNotesHit($subScore["NotesHit"] ?? null);
                    $score->setNotesMissed($subScore["NotesMissed"] ?? null);
                    $score->setNotesNotProcessed($subScore["NotesNotProcessed"] ?? null);
                    $score->setHitAccuracy($subScore["HitAccuracy"] ?? null);
                    $score->setHitSpeed($subScore["HitSpeed"] ?? null);
                    if ($season != null && $songDiff->isRanked()) {
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
                $scoreHistory->setPercentage($subScore["Percentage"] ?? null);
                $scoreHistory->setPercentage2($subScore["Percentage2"] ?? null);
                $scoreHistory->setCombos($subScore["Combos"] ?? null);
                $scoreHistory->setNotesHit($subScore["NotesHit"] ?? null);
                $scoreHistory->setNotesMissed($subScore["NotesMissed"] ?? null);
                $scoreHistory->setNotesNotProcessed($subScore["NotesNotProcessed"] ?? null);
                $scoreHistory->setHitAccuracy($subScore["HitAccuracy"] ?? null);
                $scoreHistory->setHitSpeed($subScore["HitSpeed"] ?? null);
                $em->flush();
                if ($score->getScore() < $scoreData) {
                    $score->setScore($scoreData);
                    $score->setPercentage($subScore["Percentage"] ?? null);
                    $score->setPercentage2($subScore["Percentage2"] ?? null);
                    $score->setCombos($subScore["Combos"] ?? null);
                    $score->setNotesHit($subScore["NotesHit"] ?? null);
                    $score->setNotesMissed($subScore["NotesMissed"] ?? null);
                    $score->setNotesNotProcessed($subScore["NotesNotProcessed"] ?? null);
                    $score->setHitAccuracy($subScore["HitAccuracy"] ?? null);
                    $score->setHitSpeed($subScore["HitSpeed"] ?? null);
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
                $logger->error("API : " . $apiKey . " " . $hash . " " . $subScore["Level"] . " 3_SCORE_NOT_SAVED : " . $e->getMessage());

//                return new JsonResponse($results,400);

            }
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
            ->setParameter('search_string', '%' . $term . '%')
            ->getQuery()->getResult();
        $songs = [];

        /** @var Song $song */
        foreach ($songsEntities as $song) {
            $songs[] = [
                "Id" => $song->getId(),
                "Name" => $song->getName(),
                "IsRanked" => $song->isRanked(),
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
                "IsRanked" => $song->isRanked(),
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
                "IsRanked" => $song->isRanked(),
                "Hash" => $song->getNewGuid(),
                "Mapper" => $song->getLevelAuthorName(),
                "Difficulties" => $song->getSongDifficultiesStr(),
                "CoverImageExtension" => $song->getCoverImageExtension(),
            ]
        );
    }

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
        foreach ($data as $subScore) {

            $song = $songRepository->findOneBy(['newGuid' => $subScore["HashInfo"]]);
            if ($song == null) {
                $overlay->setDifficulty(null);
                $overlay->setStartAt(null);
                $em->flush();
                continue;
            }
            $rank = $difficultyRankRepository->findOneBy(['level' => $subScore['Level']]);
            $songDiff = $songDifficultyRepository->findOneBy([
                'song' => $song,
                "difficultyRank" => $rank
            ]);

            if ($songDiff == null) {
                $overlay->setDifficulty(null);
                $overlay->setStartAt(null);
                $em->flush();
                continue;
            }

            $overlay->setDifficulty($songDiff);
            $overlay->setStartAt(new DateTime());
            $em->flush();
        }
        return new Response("OK");
    }

}
