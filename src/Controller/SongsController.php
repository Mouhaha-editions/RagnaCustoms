<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\Vote;
use App\Repository\SongRepository;
use App\Repository\VoteRepository;
use App\Service\DiscordService;
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
     * @Route("/song/test", name="song_test")
     */
    public function test(DiscordService $discordService, SongRepository $songRepository)
    {
//        $discordService->sendNewSongMessage($songRepository->find(5));


        return $this->render('songs/test.html.twig');
    }

    /**
     * @Route("/song/vote/up/{id}", name="song_vote_up")
     * @param Request $request
     * @param Song $song
     * @param VoteRepository $voteRepository
     * @return Response
     */
    public function voteUp(Request $request, Song $song, VoteRepository $voteRepository): Response
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
            $vote->setKind(Vote::KIND_UP);
            $em->persist($vote);
            $song->setVoteUp($song->getVoteUp() + 1);

        } elseif ($vote->getKind() == Vote::KIND_UP) {
            $vote->setKind(Vote::KIND_NEUTRAL);
            $song->setVoteUp($song->getVoteUp() - 1);
        } else {
            if ($vote->getKind() == Vote::KIND_DOWN) {
                $song->setVoteDown($song->getVoteDown() - 1);
            }
            $song->setVoteUp($song->getVoteUp() + 1);
            $vote->setKind(Vote::KIND_UP);
        }
        $em->flush();
        return new JsonResponse([
            "error" => false,
            "errorMessage" => false,
            "result" => $this->renderView("songs/partial/vote.html.twig", ['song' => $song]),
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
        $qb->andWhere('s.moderated = true');
        if ($request->get('search', null)) {
            $qb->andWhere('(s.name LIKE :search_string OR s.authorName LIKE :search_string OR s.levelAuthorName LIKE :search_string)')
                ->setParameter('search_string', '%' . $request->get('search', null) . '%');
        }

        $qb->orderBy('s.createdAt', 'DESC');
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

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $song->getId() . '.zip'
        );
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }
}
