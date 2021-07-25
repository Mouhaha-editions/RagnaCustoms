<?php

namespace App\Controller;

use App\Entity\Score;
use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Repository\DifficultyRankRepository;
use App\Repository\ScoreRepository;
use App\Repository\SeasonRepository;
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

    const CurrentVersion = "1.2.4";

    /**
     * @Route("/api/score/v2", name="api_score_v2")
     */
    public function scoreV2(Request $request,SeasonRepository $seasonRepository, DifficultyRankRepository $difficultyRankRepository, SongDifficultyRepository $songDifficultyRepository, ScoreRepository $scoreRepository, UtilisateurRepository $utilisateurRepository, SongRepository $songRepository, LoggerInterface $logger): Response
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
                "ranked"=> $ranked,
                "level" => "",
                "message" => "Score not saved (no data) ",
                "success" => false,
                "error" => "0_NO_CONTENT"
            ];
            return new JsonResponse($results);
        }
        if($data["AppVersion"] < self::CurrentVersion){
            $results[] = [
                "user" => $apiKey,
                "hash" => "all",
                "ranked"=> $ranked,
                "level" => "",
                "message" => "Score not saved (wrong app version, need : ".(self::CurrentVersion)." get ".$data["AppVersion"]." )",
                "success" => false,
                "error" => "0_WRONG_APP"
            ];
        }
        /** @var Utilisateur $user */
        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
        if ($user == null) {
            $results[] = [
                "user" => $apiKey,
                "hash" => "all",
                "level" => "",
                "ranked"=> $ranked,
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

        $season = $seasonRepository->createQueryBuilder('s')
            ->where('s.startDate <= :now')
            ->andWhere('s.endDate >= :now')
            ->setParameter('now', new \DateTime())
            ->setFirstResult(0)->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();



        foreach ($data as $subScore) {
            try {
                $song = $songRepository->findOneBy(['newGuid' => $subScore["HashInfo"]]);
                if ($song == null) {
                    $results[] = [
                        "hash" => $subScore["HashInfo"],
                        "level" => $subScore["Level"],
                        "message" => "Score not saved (song not found) ",
                        "ranked"=> $ranked,
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
                        "ranked"=> $ranked,
                        "message" => "Score not saved (level not found) ",
                        "success" => false,
                        "error" => "2_LEVEL_NOT_FOUND"
                    ];
                    $logger->error("API : " . $apiKey . " " . $subScore["HashInfo"] . " " . $subScore["Level"] . " 2_LEVEL_NOT_FOUND");
                    continue;
//                    return new JsonResponse($results,400);
                }

                if($season != null && $songDiff->isRanked()) {
                    $score = $scoreRepository->findOneBy([
                        'user' => $user,
                        'songDifficulty' => $songDiff,
                        'season' => $season
                    ]);
                    if($score != null){
                        $ranked = true;
                    }
                }else{
                    $score = $scoreRepository->findOneBy([
                        'user' => $user,
                        'songDifficulty' => $songDiff,
                        'season' => null
                    ]);
                }
                if ($score == null) {
                    $score = new Score();
                    $score->setUser($user);
                    $score->setSongDifficulty($songDiff);
                    if($season != null && $songDiff->isRanked()) {
                        $score->setSeason($season);
                        $ranked = true;
                    }
                    $em->persist($score);
                }
                $scoreData = round(floatval($subScore['Score']) / 100, 2);
                $oldscore = $score->getScore();
               if($score->getScore() < $scoreData) {
                   $score->setScore($scoreData);
                   if ($score->getScore() >= 99000) {
                       $score->setScore($score->getScore() / 1000000);
                   }
                   $em->flush();
                   $results[] = [
                       "hash" => $subScore["HashInfo"],
                       "level" => $subScore["Level"],
                       "success" => true,
                       "ranked"=> $ranked,
                       "message" => "Score saved (old score : ".$oldscore." < new score : ".$scoreData.") ",
                       "error" => "SUCCESS"
                   ];
               }else{
                   $em->flush();
                   $results[] = [
                       "hash" => $subScore["HashInfo"],
                       "level" => $subScore["Level"],
                       "success" => true,
                       "ranked"=> $ranked,
                       "message" => "Score not saved (old score : ".$oldscore." >= new score : ".$scoreData.")",
                       "error" => "SUCCESS"
                   ];

               }


                $results[] = [
                    "hash" => $subScore["HashInfo"],
                    "level" => $subScore["Level"],
                    "success" => true,
                    "message" => "Score saved",
                    "error" => "SUCCESS"
                ];
            } catch (Exception $e) {
                $results[] = [
                    "hash" => $subScore["HashInfo"],
                    "level" => $subScore["Level"],
                    "success" => false,
                    "error" => "3_SCORE_NOT_SAVED",
                    "message" => "Score not saved because of an unexpected error",
                    'deatil'=>$e->getMessage()
                ];
                $logger->error("API : " . $apiKey . " " . $subScore["HashInfo"] . " " . $subScore["Level"] . " 3_SCORE_NOT_SAVED : " . $e->getMessage());

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
