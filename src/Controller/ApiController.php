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

    const CurrentVersion = "3.0.0";

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
        $songs = $songRepository->createQueryBuilder('s')->select('s.id, s.name, s.authorName AS author, s.levelAuthorName AS mapper, s.newGuid AS hash, GROUP_CONCAT(r.level) AS Difficulties')->leftJoin("s.songDifficulties", 'sd')->leftJoin('sd.difficultyRank', 'r')->where("s.isDeleted != 1")->groupBy('s.id')->getQuery()->getArrayResult();

        return new JsonResponse($songs);
    }



    /**
     * @Route("/api/search/{term}", name="api_search")
     */
    public function index(Request $request, string $term = null, SongRepository $songRepository): Response
    {
        $songsEntities = $songRepository->createQueryBuilder('s')->where('(s.name LIKE :search_string OR s.authorName LIKE :search_string OR s.levelAuthorName LIKE :search_string)')->andWhere('s.moderated = true')->andWhere('s.isDeleted != true')->setParameter('search_string', '%' . $term . '%')->getQuery()->getResult();
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
            ]);
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
            ]);
    }
    /**
     * @param Request $request
     * @Route("/api/song-list/{id}", name="api_song_list")
     */
    public function songList(Request $request, SongTemporaryList $songTemporaryList)
    {
        $data = [];
        foreach($songTemporaryList->getSongs() AS $song){
            $data[] = [
                "Id" => $song->getId(),
                "Name" => $song->getName(),
                "Author" => $song->getAuthorName(),
                "IsRanked" => $song->isSeasonRanked(),
                "Hash" => $song->getNewGuid(),
                "Mapper" => $song->getLevelAuthorName(),
                "Difficulties" => $song->getSongDifficultiesStr(),
                "CoverImageExtension" => $song->getCoverImageExtension(),
            ];
        }
        return new JsonResponse($data);
    }

    /**
     * @Route("/api/hash/{hash}", name="api_hash")
     */
    public function hash(Request $request, string $hash, SongRepository $songRepository): Response
    {
        $song = $songRepository->createQueryBuilder('s')->where('s.newGuid LIKE :search_string)')->andWhere('s.moderated = true')->setParameter('search_string', $hash)->getQuery()->setFirstResult(0)->setMaxResults(1)->getOneOrNullResult();
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
            ]);
    }

    /**
     * @Route("/api/overlay/", name="api_overlay")
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
        $song = $songRepository->findOneBy(['newGuid' => $data["Song"]["Hash"]]);
        if ($song == null) {
            $overlay->setDifficulty(null);
            $overlay->setStartAt(null);
            $em->flush();
            return new Response("NOK", 500);
        }
        $rank = $difficultyRankRepository->findOneBy(['level' => $data["Song"]['Level']]);
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

    /**
     * @Route("/api/overlay/clean/", name="api_overlay_clear")
     * @param Request $request
     * @param UtilisateurRepository $utilisateurRepository
     * @param OverlayRepository $overlayRepository
     * @return Response
     */
    public function overlayClean(Request $request, UtilisateurRepository $utilisateurRepository, OverlayRepository $overlayRepository): Response
    {
        $apiKey = $request->headers->get('x-api-key');

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
        $overlay->setDifficulty(null);
        $overlay->setStartAt(null);
        $em->flush();
        return new Response("OK");
    }

    /**
     * @param Request $request
     * @Route("/api/song-categories", name="api_song_categories")
     */
    public function songCategories(Request $request, SongCategoryRepository $categoryRepository)
    {
        $data = $categoryRepository->createQueryBuilder("sc")->select("sc.id AS id, sc.label AS text")->where('sc.label LIKE :search')->setParameter('search', '%' . $request->get('q') . '%')->andWhere('sc.isOnlyForAdmin = false')->orderBy('sc.label')->getQuery()->getArrayResult();
        return new JsonResponse([
            'results' => $data
        ]);
    }

}
