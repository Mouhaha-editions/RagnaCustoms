<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\Vote;
use App\Repository\SongRepository;
use App\Repository\VoteRepository;
use App\Service\DiscordService;
use App\Service\VoteService;
use Discord\Discord;
use Discord\WebSockets\Intents;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class SongsController extends AbstractController
{
    /**
     * @Route("/song/detail/{id}", name="song_detail")
     */
    public function songDetail(Request $request, Song $song)
    {
        $em = $this->getDoctrine()->getManager();
        $song->setViews($song->getViews() + 1);
        $em->flush();
        return $this->render('songs/detail.html.twig', ['song' => $song]);
    }

    /**
     * }
     * @Route("/song/form/review/{id}", name="form_review_save")
     */
    public function formReviewSave(Request $request, Song $song, VoteRepository $voteRepository, VoteService $voteService)
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => "You need an account to vote !",
                "response" => "You need an account to vote !",
            ]);
        }
        if ($song == null) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => "You need an account to vote !",
                "response" => "Song not found !",
            ]);
        }
        if ($song->getUser() == $this->getUser()) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => "You need an account to vote !",
                "response" => "You can't review a song you submit",
            ]);
        }
        $em = $this->getDoctrine()->getManager();
        $vote = $voteRepository->findOneBy([
            'song' => $song,
            'user' => $this->getUser()
        ]);
        if ($vote == null) {
            $vote = new Vote();
            $vote->setSong($song);
            $vote->setUser($this->getUser());
            $em->persist($vote);
        } else {
            $voteService->subScore($song, $vote);
        }
        $vote->setFunFactor($request->get('funFactor'));
        $vote->setRhythm($request->get('rhythm'));
        $vote->setFlow($request->get('flow'));
        $vote->setPatternQuality($request->get('patternQuality'));
        $vote->setReadability($request->get('readability'));
        $vote->setLevelQuality($request->get('levelQuality'));

        $voteService->addScore($song, $vote);
        $em->flush();
        return new JsonResponse([
            "error" => false,
            "errorMessage" => false,
            "response" => $this->renderView("songs/partial/vote.html.twig", [
                'song' => $song,
                "vote" => $vote
            ]),
        ]);
    }

    /**
     * @Route("/song/review/{id}", name="song_review")
     * @param Request $request
     * @param Song $song
     * @param VoteRepository $voteRepository
     * @return Response
     */
    public function voteUp(Request $request, Song $song, VoteRepository $voteRepository): Response
    {
        if ($song == null) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => "You need an account to vote !",
                "response" => "Song not found !",
            ]);
        }

        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => "You need an account to vote !",
                "response" => $this->renderView('songs/partial/detail_vote.html.twig', [
                    "song" => $song,
                    'message' => "You need an account to vote !"
                ])
            ]);
        }

        if ($song->getUser() == $this->getUser()) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => "You need an account to vote !",
                "response" => $this->renderView('songs/partial/detail_vote.html.twig', [
                    "song" => $song,
                    'message' => "You can't review a song you submit"
                ])
            ]);
        }
        $vote = $voteRepository->findOneBy([
            'song' => $song,
            'user' => $this->getUser()
        ]);
        if ($vote == null) {
            $vote = new Vote();
        }
        return new JsonResponse([
            "error" => false,
            "errorMessage" => false,
            "response" => $this->renderView("songs/partial/form_review.html.twig", [
                'song' => $song,
                "vote" => $vote
            ]),
        ]);
    }

    /**
     * @Route("/song/vote/down/{id}", name="song_vote_down")
     * @param Request $request
     * @param Song $song
     * @param VoteRepository $voteRepository
     * @return Response
     */
    public function voteDown(Request $request, Song $song, VoteRepository $voteRepository): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => "You need an account to vote !",
                "result" => null,
            ]);
        }
        $em = $this->getDoctrine()->getManager();
        $vote = $voteRepository->findOneBy([
            'song' => $song,
            'user' => $this->getUser()
        ]);
        if ($vote == null) {
            $vote = new Vote();
            $vote->setSong($song);
            $vote->setUser($this->getUser());
            $vote->setKind(Vote::KIND_DOWN);
            $em->persist($vote);
            $song->setVoteDown($song->getVoteDown() + 1);
        } elseif ($vote->getKind() == Vote::KIND_DOWN) {
            $vote->setKind(Vote::KIND_NEUTRAL);
            $song->setVoteDown($song->getVoteDown() - 1);
        } else {
            $vote->setKind(Vote::KIND_DOWN);
            $song->setVoteDown($song->getVoteDown() + 1);
            if ($vote->getKind() == Vote::KIND_UP) {
                $song->setVoteUp($song->getVoteUp() - 1);
            }
        }
        $em->flush();
        return new JsonResponse([
            "error" => false,
            "errorMessage" => false,
            "result" => $this->renderView("songs/partial/vote.html.twig", ['song' => $song]),
        ]);
    }


    /**
     * @Route("/", name="home")
     * @param Request $request
     * @param SongRepository $songRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    public function index(Request $request, SongRepository $songRepository, PaginationService $paginationService): Response
    {
        $qb = $this->getDoctrine()->getRepository(Song::class)->createQueryBuilder("s");
        if ($request->get('downloads_filter_difficulties', null)) {
            $qb->leftJoin('s.songDifficulties', 'song_difficulties')
                ->leftJoin('song_difficulties.difficultyRank', 'rank');
            switch ($request->get('downloads_filter_difficulties')) {
                case 1:
                    $qb->where('rank.level BETWEEN 1 and 3');
                    break;
                case 2 :
                    $qb->where('rank.level BETWEEN 4 and 7');
                    break;
                case 3 :
                    $qb->where('rank.level BETWEEN 8 and 10');

                    break;
            }
        }
        if ($request->get('downloads_filter_order', null)) {

            switch ($request->get('downloads_filter_order')) {
                case 1:
                    $qb->orderBy('s.totalVotes/s.countVotes', 'DESC');
                    break;
                case 2 :
                    $qb->orderBy('s.approximativeDuration', 'DESC');
                    break;
                case 3 :
                    $qb->orderBy('s.lastDateUpload', 'DESC');
                    break;
                default:
                    $qb->orderBy('s.createdAt', 'DESC');
                    break;
            }
        } else {
            $qb->orderBy('s.createdAt', 'DESC');
        }
        if ($request->get('converted_maps', null)) {

            switch ($request->get('converted_maps')) {
                case 1:
                    $qb->andWhere('(s.converted = false OR s.converted IS NULL)');
                    break;
                case 2 :
                    $qb->andWhere('s.converted = true');
                    break;

            }
        }
        $qb->andWhere('s.moderated = true');
        if ($request->get('search', null)) {
            $qb->andWhere('(s.name LIKE :search_string OR s.authorName LIKE :search_string OR s.levelAuthorName LIKE :search_string)')
                ->setParameter('search_string', '%' . $request->get('search', null) . '%');
        }

        $pagination = $paginationService->setDefaults(40)->process($qb, $request);
        if ($pagination->isPartial()) {
            return $this->render('songs/partial/song_row.html.twig', [
                'songs' => $pagination
            ]);
        }
        return $this->render('songs/index.html.twig', [
            'controller_name' => 'SongsController',
            'songs' => $pagination
        ]);
    }

    /**
     * @Route("/songs/download/{id}", name="song_download")
     */
    public function download(Song $song, SongRepository $songRepository, KernelInterface $kernel): Response
    {
        if (!$song->isModerated()) {
            return new Response("Not available now", 403);
        }
        $em = $this->getDoctrine()->getManager();
        $song->setDownloads($song->getDownloads() + 1);
        $em->flush();
        $fileContent = file_get_contents($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip");
        $response = new Response($fileContent);

//        $disposition = HeaderUtils::makeDisposition(
//            HeaderUtils::DISPOSITION_ATTACHMENT,
//            $song->getId() . '.zip'
//        );
//        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-type', "application/octet-stream");
        $response->headers->set('Content-Transfer-Encoding', "binary");
        $response->headers->set('Content-Length', filesize($fileContent));

        return $response;
    }

    /**
     * @Route("/songs/ddl/{id}", name="song_direct_download")
     */
    public function directDownload(Song $song, SongRepository $songRepository, KernelInterface $kernel): Response
    {
        if (!$song->isModerated()) {
            return new Response("Not available now", 403);
        }
        $em = $this->getDoctrine()->getManager();
        $song->setDownloads($song->getDownloads() + 1);
        $em->flush();
        $fileContent = file_get_contents($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip");
        $response = new Response($fileContent);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $song->getName() . '.zip'
        );
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }
}
