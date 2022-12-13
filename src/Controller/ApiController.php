<?php

namespace App\Controller;

use App\Entity\Overlay;
use App\Entity\RankedScores;
use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\SongTemporaryList;
use App\Entity\Utilisateur;
use App\Repository\DifficultyRankRepository;
use App\Repository\OverlayRepository;
use App\Repository\RankedScoresRepository;
use App\Repository\ScoreHistoryRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongCategoryRepository;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Repository\SongTemporaryListRepository;
use App\Repository\UtilisateurRepository;
use App\Service\ScoreService;
use App\Service\SongService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Sentry\State\Scope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use function Sentry\configureScope;


class ApiController extends AbstractController
{

    const CurrentVersion = "3.0.0";

    #[Route(path: '/api/php/version', name: 'php_version')]
    public function checkPhp(Request $request, SongRepository $songRepository): Response
    {
        phpinfo();
        return new Response("");
    }

    #[Route(path: '/api/login', name: 'api_login')]
    public function login(Request $request, UtilisateurRepository $utilisateurRepository, UserPasswordHasherInterface $hasher): Response
    {
        /** @var Utilisateur $user */
        $user = $utilisateurRepository->findBy(['username' => $request->get('username')]);
        if ($user !== null) {
            if ($hasher->isPasswordValid($user, $request->get('password'))) {
                return new JsonResponse(['api_key' => $user->getApiKey()]);
            }
            return new JsonResponse(['api_key' => false],400);
        }
        return new JsonResponse(['api_key' => false],400);
    }

    #[Route(path: '/api/song/check-updates', name: 'api_song_check_updates')]
    public function checkUpdates(Request $request, SongRepository $songRepository): Response
    {

        /**
         *  "Id" => $song->getId(),
         * "Name" => $song->getName(),
         * "Author" => $song->getAuthorName(),
         * "IsRanked" => $song->isRanked(),
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
                                ->getQuery()->getArrayResult();

        return new JsonResponse($songs);
    }


    #[Route(path: '/api/search/{term}', name: 'api_search')]
    public function index(Request $request, string $term = null, SongRepository $songRepository): Response
    {
        $songsEntities = $songRepository->createQueryBuilder('s')->where('(s.name LIKE :search_string OR s.authorName LIKE :search_string OR s.levelAuthorName LIKE :search_string)')->andWhere('s.moderated = true')->andWhere('s.isDeleted != true')->setParameter('search_string', '%' . $term . '%')->getQuery()->getResult();
        $songs = [];

        /** @var Song $song */
        foreach ($songsEntities as $song) {
            $songs[] = $song->__api();
        }

        return new JsonResponse([
            "Results" => $songs,
            "Count"   => count($songs)
        ]);
    }

    #[Route(path: '/api/song/{id}', name: 'api_song')]
    public function song(Request $request, Song $song): Response
    {
        return new JsonResponse($song->__api());
    }

    /**
     * @param Request $request
     */
    #[Route(path: '/api/song-list/{id}', name: 'api_song_list')]
    public function songList(Request $request, SongTemporaryList $songTemporaryList)
    {
        $data = [];
        foreach ($songTemporaryList->getSongs() as $song) {
            $data[] = $song->__api();
        }
        return new JsonResponse($data);
    }

    /**
     * @param int $results
     * @param ScoreHistoryRepository $scoreRepository
     * @return JsonResponse
     */
    #[Route(path: '/api/songs/last-played/{results}', name: 'api_song_last_played')]
    public function lastPlayed(int $results, ScoreHistoryRepository $scoreRepository)
    {
        /** @var ScoreHistory[] $scores */
        $scores = $scoreRepository->createQueryBuilder("score")
                                  ->leftJoin("score.songDifficulty", 'diff')
                                  ->leftJoin("diff.song", 's')
                                  ->orderBy('score.updatedAt', 'DESC')
                                  ->where('s.isDeleted != true')
                                  ->andWhere('s.wip != true')
                                  ->setFirstResult(0)
                                  ->setMaxResults($results)
                                  ->getQuery()->getResult();
        /** @var Song[] $songs */
        $songs = array_map(function (ScoreHistory $score) {
            return $score->getSongDifficulty()->getSong();
        }, $scores);
        $data = [];
        foreach ($songs as $song) {
            $data[] = $song->__api();
        }
        return new JsonResponse($data);
    }

    /**
     * @param int $results
     * @param SongRepository $songRepository
     * @return JsonResponse
     */
    #[Route(path: '/api/songs/last-uploaded/{results}', name: 'api_song_last_uploaded')]
    public function lastUploaded(int $results, SongRepository $songRepository)
    {
        /** @var Song[] $songs */
        $songs = $songRepository->createQueryBuilder("s")
                                ->orderBy("s.createdAt", 'DESC')
                                ->where('s.isDeleted != true')
                                ->andWhere('s.wip != true')
                                ->setMaxResults($results)
                                ->setFirstResult(0)
                                ->getQuery()->getResult();
        $data = [];
        foreach ($songs as $song) {
            $data[] = $song->__api();
        }
        return new JsonResponse($data);
    }

    /**
     * @param int $results
     * @param int $days
     * @param SongRepository $songRepository
     * @return JsonResponse
     */
    #[Route(path: '/api/songs/top-rated/{results}/{days}', name: 'api_song_top_rated')]
    public function topRated(int $results, int $days, SongRepository $songRepository)
    {
        /** @var Song[] $songs */
        $songs = $songRepository->createQueryBuilder("s")
                                ->addSelect('s, SUM(IF(v.votes_indc IS NULL,0,IF(v.votes_indc = 0,-1,1))) AS HIDDEN sum_votes')
                                ->leftJoin("s.voteCounters", 'v')
                                ->orderBy("sum_votes", 'DESC')
                                ->where('s.isDeleted != true')
                                ->andWhere('s.wip != true')
                                ->andWhere('v.updatedAt >= :date')
                                ->setParameter('date', (new \DateTime())->modify('-' . $days . " days"))
                                ->groupBy('s.id')
                                ->setMaxResults($results)
                                ->setFirstResult(0)
                                ->getQuery()->getResult();

        $data = [];
        foreach ($songs as $song) {
            $data[] = $song->__api();
        }
        return new JsonResponse($data);
    }

    #[Route(path: '/api/hash/{hash}', name: 'api_hash')]
    public function hash(Request $request, string $hash, SongRepository $songRepository): Response
    {
        $song = $songRepository->createQueryBuilder('s')->where('s.newGuid LIKE :search_string)')->andWhere('s.moderated = true')->setParameter('search_string', $hash)->getQuery()->setFirstResult(0)->setMaxResults(1)->getOneOrNullResult();
        if (!$song) {
            return new Response("NOK", 400);
        }
        return new JsonResponse($song->__api());
    }

    /**
     * @param Request $request
     * @param UtilisateurRepository $utilisateurRepository
     * @param DifficultyRankRepository $difficultyRankRepository
     * @param OverlayRepository $overlayRepository
     * @param SongRepository $songRepository
     * @param SongDifficultyRepository $songDifficultyRepository
     * @return Response
     */
    #[Route(path: '/api/overlay/', name: 'api_overlay')]
    public function overlay(Request $request, ManagerRegistry $doctrine, UtilisateurRepository $utilisateurRepository, DifficultyRankRepository $difficultyRankRepository, OverlayRepository $overlayRepository, SongRepository $songRepository, SongDifficultyRepository $songDifficultyRepository): Response
    {
        $data = json_decode($request->getContent(), true);
        $apiKey = $request->headers->get('x-api-key');

        if ($data == null) {
            return new Response("NOK", 500);
        }

        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
        $em = $doctrine->getManager();
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
        if (!isset($data["Song"])) return new Response("No Song", 500);

        $song = $songRepository->findOneBy(['newGuid' => $data["Song"]["Hash"]]);
        if ($song == null) {
            $overlay->setDifficulty(null);
            $overlay->setStartAt(null);
            $em->flush();
            return new Response("NOK", 500);
        }
        $rank = $difficultyRankRepository->findOneBy(['level' => $data["Song"]['Level']]);
        $songDiff = $songDifficultyRepository->findOneBy([
            'song'           => $song,
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

    /**
     * @param Request $request
     * @param UtilisateurRepository $utilisateurRepository
     * @param OverlayRepository $overlayRepository
     * @return Response
     */
    #[Route(path: '/api/overlay/clean/', name: 'api_overlay_clear')]
    public function overlayClean(Request $request, ManagerRegistry $doctrine, UtilisateurRepository $utilisateurRepository, OverlayRepository $overlayRepository): Response
    {
        $apiKey = $request->headers->get('x-api-key');

        $user = $utilisateurRepository->findOneBy(['apiKey' => $apiKey]);
        $em = $doctrine->getManager();
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
        $overlay->setDifficulty(null);
        $overlay->setStartAt(null);
        $em->flush();
        return new Response("OK");
    }

    /**
     * @param Request $request
     * @param SongCategoryRepository $categoryRepository
     * @return JsonResponse
     */
    #[Route(path: '/api/song-categories', name: 'api_song_categories')]
    public function songCategories(Request $request, SongCategoryRepository $categoryRepository)
    {
        $data = $categoryRepository->createQueryBuilder("sc")->select("sc.id AS id, sc.label AS text")->where('sc.label LIKE :search')->setParameter('search', '%' . $request->get('q') . '%')->andWhere('sc.isOnlyForAdmin = false')->orderBy('sc.label')->getQuery()->getArrayResult();
        return new JsonResponse([
            'results' => $data
        ]);
    }

}
