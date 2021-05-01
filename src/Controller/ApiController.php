<?php

namespace App\Controller;

use App\Entity\Score;
use App\Entity\Song;
use App\Repository\DifficultyRankRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Repository\UtilisateurRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
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
                    'song' => $song,
                    'songDifficulty' => $songDiff
                ]);

                if ($score == null) {
                    $score = new Score();
                    $score->setUser($user);
                    $score->setSong($song);

                    $score->setSongDifficulty($songDiff);
                    $em->persist($score);
                }

                if ($score->getScore() < str_replace(',', '.',$subScore['Score'])) {
                    $score->setScore(str_replace(',', '.', $subScore['Score']));
                }
                if($score->getScore() >= 99000){
                    $score->setScore($score->getScore()/1000000);
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
    public function scoreV2(Request $request, DifficultyRankRepository $difficultyRankRepository, SongDifficultyRepository $songDifficultyRepository, ScoreRepository $scoreRepository, UtilisateurRepository $utilisateurRepository, SongRepository $songRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $results = [];
        $apiKey = $request->headers->get('x-api-key');

        $data = json_decode($request->getContent(), true);
        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
        if ($user == null) {
            $results[] = [
                "user"=>$apiKey,
                "hash"=>"all",
                "level"=>"",
                "success"=>false,
                "error"=>"0_USER_NOT_FOUND"
            ];
            return new JsonResponse($results);
        }
        foreach ($data as $subScore) {
            try {
                $song = $songRepository->findOneBy(['newGuid' => $subScore["HashInfo"]]);
                if ($song == null) {
                    $results[] = [
                        "hash"=>$subScore["HashInfo"],
                        "level"=>$subScore["Level"],
                        "success"=>false,
                        "error"=>"1_SONG_NOT_FOUND"
                    ];
                    continue;
                }
                $rank = $difficultyRankRepository->findOneBy(['level' => $subScore['Level']]);
                $songDiff = $songDifficultyRepository->findOneBy([
                    'song' => $song,
                    "difficultyRank" => $rank
                ]);
                if ($songDiff == null) {
                    $results[] = [
                        "hash"=>$subScore["HashInfo"],
                        "level"=>$subScore["Level"],
                        "success"=>false,
                        "error"=>"2_LEVEL_NOT_FOUND"
                    ];
                    continue;
                }
                $score = $scoreRepository->findOneBy([
                    'user' => $user,
                    'song' => $song,
                    'songDifficulty' => $songDiff
                ]);

                if ($score == null) {
                    $score = new Score();
                    $score->setUser($user);
                    $score->setSong($song);

                    $score->setSongDifficulty($songDiff);
                    $em->persist($score);
                }

                if ($score->getScore() < str_replace(',', '.',$subScore['Score'])) {
                    $score->setScore(str_replace(',', '.', $subScore['Score']));
                }
                if($score->getScore() >= 99000){
                    $score->setScore($score->getScore()/1000000);
                }
                $em->flush();
                $results[] = [
                    "hash"=>$subScore["HashInfo"],
                    "level"=>$subScore["Level"],
                    "success"=>true,
                    "error"=>"SUCCESS"
                ];
            } catch (Exception $e) {
                $results[] = [
                    "hash"=>$subScore["HashInfo"],
                    "level"=>$subScore["Level"],
                    "success"=>false,
                    "error"=>"3_SCORE_NOT_SAVED"
                ];
            }
        }

        return new JsonResponse($results);
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
