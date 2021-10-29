<?php

namespace App\Controller;

use App\Entity\DownloadCounter;
use App\Entity\Playlist;
use App\Entity\Score;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\SongFeedback;
use App\Entity\ViewCounter;
use App\Entity\Vote;
use App\Form\AddPlaylistFormType;
use App\Form\SongFeedbackType;
use App\Repository\DownloadCounterRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongFeedbackRepository;
use App\Repository\SongRepository;
use App\Repository\ViewCounterRepository;
use App\Repository\VoteRepository;
use App\Service\DownloadService;
use App\Service\SongService;
use App\Service\VoteService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Contracts\Translation\TranslatorInterface;

class SongsController extends AbstractController
{
    /**
     * @Route("/songs.xml", name="sitemap_song")
     */
    public function sitemap(SongRepository $songRepository)
    {
        return $this->render('sitemap/index.html.twig', [
            'songs' => $songRepository->findBy([
                'moderated' => true,
                "wip" => false
            ])
        ]);
    }

//    /**
//     * @Route("/songs/update", name="sitemap_song")
//     */
//    public function udpate(SongRepository $songRepository, SluggerInterface $slugger)
//    {
//        $em = $this->getDoctrine()->getManager();
//        foreach ($songRepository->findAll() as $song) {
//            $song->setSlug($slugger->slug($song->getName()));
//            $em->persist($song);
//            $em->flush();
//        }
//
//        return new JsonResponse([]);
//    }

    /**
     * @Route("/rss.xml", name="rss_song")
     */
    public function rss(SongRepository $songRepository)
    {
        $songs = $songRepository->findBy([
            'moderated' => true,
            "wip" => false
        ], ['createdAt' => "Desc"]);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        /** @var ArrayCollection|Song[] $songs */
        return $this->render('rss/index.html.twig', [
            'songs' => $songs
        ], $response);
    }

    /**
     * @Route("/song/detail/{id}", name="song_detail_old")
     */
    public function songDetailId(Request $request, ScoreRepository $scoreRepository, Song $song, TranslatorInterface $translator, ViewCounterRepository $viewCounterRepository, SongService $songService, PaginationService $paginationService)
    {
        return $this->redirectToRoute("song_detail", ['slug' => $song->getSlug()], 301);
    }

    /**
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
                "response" => "You can't review a song you've submitted",
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
            if (!$vote->getDisabled()) {
                $voteService->subScore($song, $vote);
            }
        }
        $vote->setFunFactor($request->get('funFactor'));
        $vote->setRhythm($request->get('rhythm'));
        $vote->setFlow($request->get('flow'));
        $vote->setPatternQuality($request->get('patternQuality'));
        $vote->setReadability($request->get('readability'));
        $vote->setLevelQuality($request->get('levelQuality'));
        $vote->setDisabled(false);
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
    public function songReview(Request $request, Song $song, VoteRepository $voteRepository, TranslatorInterface $translator): Response
    {
        if ($song == null) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account to vote !"),
                "response" => $translator->trans("Custom song not found !"),
            ]);
        }

        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account to vote !"),
                "response" => $this->renderView('songs/partial/detail_vote.html.twig', [
                    "song" => $song,
                    'message' => $translator->trans("You need an account to vote !")
                ])
            ]);
        }

        if ($song->getUser() == $this->getUser()) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account to vote !"),
                "response" => $this->renderView('songs/partial/detail_vote.html.twig', [
                    "song" => $song,
                    'message' => $translator->trans("You can't review a custom song you've submitted")
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
     * @Route("/song/playlist/{id}", name="song_playlist")
     * @param Request $request
     * @param Song $song
     * @param TranslatorInterface $translator
     * @param SongDifficultyRepository $songDifficultyRepository
     * @return JsonResponse
     */
    public function formPlaylist(Request $request, Song $song, TranslatorInterface $translator)
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account to use playlist!"),
                "response" => $translator->trans("You need an account to use playlist!"),
            ]);
        }

        if ($song == null) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("Song not found!"),
                "response" => $translator->trans("Song not found!"),

            ]);
        }

        $form = $this->createForm(AddPlaylistFormType::class, $this->getUser(), [
            'attr' => [
                'class' => "form ajax-form",
                'method' => "post",
                "action" => $this->generateUrl("song_playlist", ["id" => $song->getId()]),
                "data-url" => $this->generateUrl("song_playlist", ["id" => $song->getId()])
            ]
        ]);

        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Playlist $playlist */
            $playlist = $form->get('playlist')->getData();
            if ($playlist == null) {
                $label = trim($form->get('newPlaylist')->getData());
                if ($label == null || empty($label)) {
                    return new JsonResponse([
                        "error" => true,
                        "errorMessage" => $translator->trans("Playlist have to be named!"),
                        "response" => $translator->trans("Playlist have to be named!"),

                    ]);
                }
                $playlist = new Playlist();
                $playlist->setLabel($label);
                $this->getUser()->addPlaylist($playlist);
                $playlist->setUser($this->getUser());
                $em->persist($playlist);
            }
            foreach ($playlist->getSongs() AS $psong){
                if($song->getId() === $psong->getId()){
                    return new JsonResponse([
                        "error" => true,
                        "errorMessage" => $translator->trans("Song already in playlist!"),
                        "response" => $translator->trans("Song already in playlist!"),
                    ]);
                }
            }
            $playlist->addSong($song);
            $em->flush();
            return new JsonResponse([
                "error" => false,
                "errorMessage" => "You need an account to vote !",
                "response" => "<div class='alert alert-success'>" . $translator->trans("Song added to your playlist!") . "</div>",

            ]);
        }

        return new JsonResponse([
            "error" => false,
            "errorMessage" => false,
            "response" => $this->renderView("songs/partial/form_playlist.html.twig", [
                'form' => $form->createView(),
                'song' => $song,
            ]),
        ]);
    }

    /**
     * @Route("/song/feedback/{id}", name="song_feedback")
     * @param Request $request
     * @param Song $song
     * @param TranslatorInterface $translator
     * @param SongDifficultyRepository $songDifficultyRepository
     * @return JsonResponse
     */
    public function formFeedback(Request $request, Song $song, TranslatorInterface $translator,
                                 SongDifficultyRepository $songDifficultyRepository)
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account to send a feedback!"),
                "response" => $translator->trans("You need an account to send a feedback!"),
            ]);
        }

        if ($song == null) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => "You need an account to vote !",
                "response" => $translator->trans("Song not found!"),

            ]);
        }
        $feedback = new SongFeedback();
        $feedback->setUser($this->getUser());
        $feedback->setHash($song->getNewGuid());
        $form = $this->createForm(SongFeedbackType::class, $feedback, [
            'attr' => [
                'class' => "form ajax-form",
                'method' => "post",
                "action" => $this->generateUrl("song_feedback", ["id" => $song->getId()]),
                "data-url" => $this->generateUrl("song_feedback", ["id" => $song->getId()])
            ]
        ]);

        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {
            $dif = $form->get('songDifficulty')->getData();
            if ($dif != null) {
                $feedback->setDifficulty($dif->getDifficultyRank()->getLevel());
            }
            $em->persist($feedback);
            $em->flush();
            return new JsonResponse([
                "error" => false,
                "errorMessage" => "You need an account to vote !",
                "response" => "<div class='alert alert-success'>" . $translator->trans("Your review has been sent!") . "</div>",

            ]);
        }

        return new JsonResponse([
            "error" => false,
            "errorMessage" => false,
            "response" => $this->renderView("songs/partial/form_feedback.html.twig", [
                'form' => $form->createView(),
                'song' => $song,
                "vote" => $feedback
            ]),
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
        $qb = $this->getDoctrine()
            ->getRepository(Song::class)
            ->createQueryBuilder("s");
        $wip = false;

        if ($request->get('downloads_filter_difficulties', null)) {
            $qb->leftJoin('s.songDifficulties', 'song_difficulties')
                ->leftJoin('song_difficulties.difficultyRank', 'rank');
            switch ($request->get('downloads_filter_difficulties')) {
                case 1:
                    $qb->andWhere('rank.level BETWEEN 1 and 3');
                    break;
                case 2 :
                    $qb->andWhere('rank.level BETWEEN 4 and 7');
                    break;
                case 3 :
                    $qb->andWhere('rank.level BETWEEN 8 and 10');
                    break;
                case 4 :
                    $qb->leftJoin('song_difficulties.seasons', 'season');
                    $qb->andWhere('season.startDate <= :now ')
                        ->andWhere('season.endDate >= :now')
                        ->setParameter('now', new DateTime());
                    break;
                case 5 :
                    $wip = true;
                    break;
            }
        }
        $qb->andWhere("s.wip = :wip")
            ->setParameter("wip", $wip);
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
                case 4 :
                    $qb->orderBy('s.name', 'ASC');
                    break;
                default:
                    $qb->orderBy('s.lastDateUpload', 'DESC');
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
            $exp = explode(':', $request->get('search'));
            switch ($exp[0]) {
                case 'mapper':
                    $qb->andWhere('(s.levelAuthorName LIKE :search_string)')
                        ->setParameter('search_string', '%' . $exp[1] . '%');
                    break;
                case 'artist':
                    $qb->andWhere('(s.authorName LIKE :search_string)')
                        ->setParameter('search_string', '%' . $exp[1] . '%');
                    break;
                case 'title':
                    $qb->andWhere('(s.name LIKE :search_string)')
                        ->setParameter('search_string', '%' . $exp[1] . '%');
                    break;
                case 'desc':
                    $qb->andWhere('(s.description LIKE :search_string)')
                        ->setParameter('search_string', '%' . $exp[1] . '%');
                    break;
                default:
                    $qb->andWhere('(s.name LIKE :search_string OR s.authorName LIKE :search_string OR s.description LIKE :search_string OR s.levelAuthorName LIKE :search_string)')
                        ->setParameter('search_string', '%' . $request->get('search', null) . '%');
            }
        }

        if ($request->get('onclick_dl')) {
            $ids = $qb->select('s.id')->getQuery()->getArrayResult();
            return $this->redirect("ragnac://install/" . implode('-', array_map(function ($id) {
                    return array_pop($id);
                }, $ids)));
        }
        $qb->andWhere("s.isDeleted != true");
        $pagination = $paginationService->setDefaults(72)->process($qb, $request);
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
    public function download(Request $request, Song $song, KernelInterface $kernel, DownloadService $downloadService, DownloadCounterRepository $downloadCounterRepository): Response
    {
        if (!$song->isModerated()) {
            return new Response("Not available now", 403);
        }
        $em = $this->getDoctrine()->getManager();
        $song->setDownloads($song->getDownloads() + 1);
        $em->flush();

        $fileContent = file_get_contents($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip");
        $downloadService->addOne($song);

        $response = new Response($fileContent);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $song->getId() . '.zip'
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-type', "application/octet-stream");
        $response->headers->set('Content-Transfer-Encoding', "binary");
        $response->headers->set('Content-Length', filesize($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip"));

        return $response;
    }

    /**
     * @Route("/songs/ddl/{id}", name="song_direct_download")
     */
    public function directDownload(Song $song, KernelInterface $kernel, DownloadService $downloadService): Response
    {
        if (!$song->isModerated()) {
            return new Response("Not available now", 403);
        }
        $em = $this->getDoctrine()->getManager();
        $song->setDownloads($song->getDownloads() + 1);
        $em->flush();
        $downloadService->addOne($song);

        $fileContent = file_get_contents($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip");
        $response = new Response($fileContent);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $this->cleanName($song->getName()) . '.zip'
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-type', "application/octet-stream");
        $response->headers->set('Content-Transfer-Encoding', "binary");
        $response->headers->set('Content-Length', filesize($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip"));
        return $response;
    }

    private function cleanName(?string $getName)
    {
        return preg_replace('/[^a-zA-Z]/i', '', $getName);
    }


    /**
     * @Route("/song/{slug}", name="song_detail", defaults={"slug"=null})
     */
    public function songDetail(Request $request, ScoreRepository $scoreRepository, Song $song, TranslatorInterface $translator, ViewCounterRepository $viewCounterRepository, SongService $songService, PaginationService $paginationService)
    {
        if ((!$song->isModerated() && !$this->isGranted('ROLE_ADMIN') && $song->getUser() != $this->getUser()) || $song->getIsDeleted()) {
            $this->addFlash('warning', $translator->trans("This custom song is not available for now"));
            return $this->redirectToRoute('home');
        }
        $em = $this->getDoctrine()->getManager();
        $song->setViews($song->getViews() + 1);
        $feedback = new SongFeedback();
        $feedback->setHash($song->getNewGuid());
        $feedback->setUser($this->getUser());
        $feedbackForm = $this->createForm(SongFeedbackType::class, $feedback);
        $ip = $request->getClientIp();
        $dlu = $viewCounterRepository->findOneBy([
            'song' => $song,
            "ip" => $ip
        ]);
        if ($dlu == null) {
            $dlu = new ViewCounter();
            $dlu->setSong($song);
            $dlu->setUser($this->getUser());
            $dlu->setIp($ip);
            $em->persist($dlu);
            $em->flush();
        }
        $feedbackForm->handleRequest($request);
        $em = $this->getDoctrine()->getManager();

        if ($feedbackForm->isSubmitted() && $feedbackForm->isValid() && $this->getUser() != null) {
            $dif = $feedbackForm->get('songDifficulty')->getData();
            if ($dif != null) {
                $feedback->setDifficulty($dif->getDifficultyRank()->getLevel());
            }
            $em->persist($feedback);
            $em->flush();
            try {
                $songService->newFeedback($feedback);
            } catch (Exception $e) {

            }
            $feedback = new SongFeedback();
            $feedback->setHash($song->getNewGuid());
            $feedback->setUser($this->getUser());
            $feedbackForm = $this->createForm(SongFeedbackType::class, $feedback);
            $this->addFlash("success", $translator->trans("Feedback sent!"));
        }
        $songService->emulatorFileDispatcher($song);
        $em->flush();

        $levels = [];
        foreach ($song->getSongDifficulties() as $difficulty) {
            $level = $difficulty->getDifficultyRank()->getLevel();
            $scores = $this->getDoctrine()->getRepository(Score::class)->createQueryBuilder('s')
                ->select('s, MAX(s.score) AS HIDDEN max_score')
                ->where('s.difficulty = :diff')
                ->andWhere('s.hash = :hash')
                ->setParameter('diff', $level)
                ->setParameter('hash', $difficulty->getSong()->getNewGuid())
                ->groupBy('s.user')
                ->orderBy('max_score', 'DESC');

            $pagination = $paginationService->setDefaults(50)->process($scores, $request);
            $levels [] = [
                "level" => $level,
                'scores' => $pagination
            ];
        }

        return $this->render('songs/detail.html.twig', [
            'song' => $song,
            'levels' => $levels,
            "feedbackForm" => $feedbackForm->createView()
        ]);
    }


}
