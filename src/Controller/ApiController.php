<?php

namespace App\Controller;

use App\Entity\Score;
use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Repository\DifficultyRankRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Repository\UtilisateurRepository;
use App\Service\SongService;
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
    /**
     * @Route("/ai", name="ai")
     */
    public function ai(Request $request, SongService $songService): Response
    {
        $songService->AiMap();
        return new Response("OK");
    }


    /**
     * @Route("/api/score", name="api_score")
     */
    public function score(Request $request, DifficultyRankRepository $difficultyRankRepository, SongDifficultyRepository $songDifficultyRepository, ScoreRepository $scoreRepository, UtilisateurRepository $utilisateurRepository, SongRepository $songRepository): Response
    {
        $em = $this->getDoctrine()->getManager();

        $data = json_decode($request->getContent(), true);
        $user = $utilisateurRepository->findOneBy(['apiKey' => $data['ApiKey']]);
        if ($user == null) {
            return new Response('NOK');
        }
        foreach ($data['Scores'] as $subScore) {
            $score = null;
            try {
                $song = $songRepository->findOneBy(['guid' => $subScore["HashInfo"]]);
                if ($song == null) {
                    continue;
                }

                if ($song == null) {
                    continue;
                }
                $rank = $difficultyRankRepository->findOneBy(['level' => $subScore['Level']]);
                $songDiff = $songDifficultyRepository->findOneBy([
                    'song' => $song,
                    "difficultyRank" => $rank
                ]);
                if ($songDiff == null) {
                    continue;
                }
                $score = $scoreRepository->findOneBy([
                    'user' => $user,
                    'songDifficulty' => $songDiff
                ]);

                if ($score == null) {
                    $score = new Score();
                    $score->setUser($user);
                    $score->setSongDifficulty($songDiff);
                    $em->persist($score);
                }

                if ($score->getScore() < floatval(str_replace(',', '.', $subScore['Score']))) {
                    $score->setScore(floatval(str_replace(',', '.', $subScore['Score'])));
                }
                if ($score->getScore() >= 99000) {
                    $score->setScore($score->getScore() / 1000000);
                }
                $em->flush();

            } catch (Exception $e) {
                $x = $e;
            }
        }

        return new Response("OK");
    }

    /**
     * @Route("/api/score/v2", name="api_score_v2")
     */
    public function scoreV2(Request $request, DifficultyRankRepository $difficultyRankRepository, SongDifficultyRepository $songDifficultyRepository, ScoreRepository $scoreRepository, UtilisateurRepository $utilisateurRepository, SongRepository $songRepository, LoggerInterface $logger): Response
    {
        $em = $this->getDoctrine()->getManager();
        $results = [];
        $apiKey = $request->headers->get('x-api-key');

        $data = json_decode($request->getContent(), true);
        if ($data == null) {
            $logger->error("no data");
            $results[] = [
                "user" => $apiKey,
                "hash" => "all",
                "level" => "",
                "success" => false,
                "error" => "0_NO_CONTENT"
            ];
            return new JsonResponse($results);
        }
        /** @var Utilisateur $user */
        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
        if ($user == null) {
            $results[] = [
                "user" => $apiKey,
                "hash" => "all",
                "level" => "",
                "success" => false,
                "error" => "0_USER_NOT_FOUND"
            ];
            $logger->error("API : " . $apiKey . " USER NOT FOUND");
            return new JsonResponse($results, 400);
        }
        configureScope(function (Scope $scope) use ($user): void {
            $scope->setUser(['username' => $user->getUsername()]);
        });
        foreach ($data as $subScore) {
            try {
                $song = $songRepository->findOneBy(['newGuid' => $subScore["HashInfo"]]);
                if ($song == null) {
                    $results[] = [
                        "hash" => $subScore["HashInfo"],
                        "level" => $subScore["Level"],
                        "success" => false,
                        "error" => "1_SONG_NOT_FOUND"
                    ];
                    $logger->error("API : " . $apiKey . " " . $subScore["HashInfo"] . " 1_SONG_NOT_FOUND");
                    continue;
//                    return new JsonResponse($results,400);
                }
                $rank = $difficultyRankRepository->findOneBy(['level' => $subScore['Level']]);
                $songDiff = $songDifficultyRepository->findOneBy([
                    'song' => $song,
                    "difficultyRank" => $rank
                ]);
                if ($songDiff == null) {
                    $results[] = [
                        "hash" => $subScore["HashInfo"],
                        "level" => $subScore["Level"],
                        "success" => false,
                        "error" => "2_LEVEL_NOT_FOUND"
                    ];
                    $logger->error("API : " . $apiKey . " " . $subScore["HashInfo"] . " " . $subScore["Level"] . " 2_LEVEL_NOT_FOUND");
                    continue;
//                    return new JsonResponse($results,400);
                }
                $score = $scoreRepository->findOneBy([
                    'user' => $user,
                    'songDifficulty' => $songDiff
                ]);
                if ($score == null) {
                    $score = new Score();
                    $score->setUser($user);
                    $score->setSongDifficulty($songDiff);
                    $em->persist($score);
                }
                $scoreData = round(floatval($subScore['Score']) / 100, 2);
               if($score->getScore() < $scoreData) {
                   $score->setScore($scoreData);
                   if ($score->getScore() >= 99000) {
                       $score->setScore($score->getScore() / 1000000);
                   }
               }


                $em->flush();
                $results[] = [
                    "hash" => $subScore["HashInfo"],
                    "level" => $subScore["Level"],
                    "success" => true,
                    "error" => "SUCCESS"
                ];
            } catch (Exception $e) {
                $results[] = [
                    "hash" => $subScore["HashInfo"],
                    "level" => $subScore["Level"],
                    "success" => false,
                    "error" => "3_SCORE_NOT_SAVED",
                    'deatil'=>$e->getMessage()
                ];
                $logger->error("API : " . $apiKey . " " . $subScore["HashInfo"] . " " . $subScore["level"] . " 3_SCORE_NOT_SAVED : " . $e->getMessage());

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
                "Mapper" => $song->getLevelAuthorName(),
                "Difficulties" => $song->getSongDifficultiesStr(),
                "CoverImageExtension" => $song->getCoverImageExtension(),
            ]
        );
    }
}
