<?php

namespace App\Controller;

use App\Entity\DifficultyRank;
use App\Entity\Playlist;
use App\Entity\Score;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\SongTemporaryList;
use App\Entity\Utilisateur;
use App\Entity\Vote;
use App\Form\AddPlaylistFormType;
use App\Form\VoteType;
use App\Repository\DownloadCounterRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongCategoryRepository;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Repository\VoteCounterRepository;
use App\Service\DiscordService;
use App\Service\DownloadService;
use App\Service\GoogleAnalyticsService;
use App\Service\ScoreService;
use App\Service\SongService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wohali\OAuth2\Client\Provider\Discord;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;


class SongsController extends AbstractController
{
    private $paginate = 30;


    /**
     * @Route("/getting-started", name="getting_started")
     * @return Response
     */
    public function gettingStarted(): Response
    {
        return $this->render('songs/getting_started.html.twig');
    }

    /**
     * @Route("/ranking-system", name="ranking_system")
     * @return Response
     */
    public function rankingSystem(): Response
    {
        return $this->render('songs/ranking_system.html.twig');
    }

    /**
     * @Route("/acceptance-criteria", name="acceptance_criteria")
     * @return Response
     */
    public function acceptanceCriteria(): Response
    {
        return $this->render('songs/acceptance_criteria.html.twig');
    }

    /**
     * @Route("/", name="home")
     * @return Response
     */
    public function homepage(): Response
    {
        return $this->render('songs/homepage.html.twig');
    }

    /**
     * @Route("/song/detail/{id}", name="song_detail_old")
     */
    public function songDetailId(Request $request, Song $song)
    {
        return $this->redirectToRoute("song_detail", ['slug' => $song->getSlug()], 301);
    }


    /**
     * @Route("/song/playlist/{id}", name="song_playlist")
     * @param Request $request
     * @param Song $song
     * @param TranslatorInterface $translator
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
            foreach ($playlist->getSongs() as $psong) {
                if ($song->getId() === $psong->getId()) {
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
     * @Route("/song-library", name="song_library")
     * @param Request $request
     * @param SongCategoryRepository $categoryRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    public function library(Request $request, SongCategoryRepository $categoryRepository, PaginationService $paginationService): Response
    {
        $filters = [];
        $qb = $this->getDoctrine()->getRepository(Song::class)->createQueryBuilder("s")->addSelect('s.voteUp - s.voteDown AS HIDDEN rating')
//            ->leftJoin("s.downloadCounters",'dc')
            ->groupBy("s.id");
//        $qb->leftJoin('s.songDifficulties', 'song_difficulties')
//            ->leftJoin('song_difficulties.difficultyRank', 'rank');
//        $qb->addSelect('s,song_difficulties');

        if ($request->get('display_wip', null) != null) {
            //$qb->andWhere("s.wip = true AND s.wip != true ");
            $filters[] = "display wip";
        } else {
            $qb->andWhere("s.wip != true");
        }
        $qb->leftJoin('s.songDifficulties', 'song_difficulties');

        if ($request->get('only_ranked', null) != null) {
            $qb->andWhere("song_difficulties.isRanked = true");
            $filters[] = "only ranked";

        }
        if ($request->get('downloads_filter_difficulties', null)) {
            $qb->leftJoin('song_difficulties.difficultyRank', 'rank');
            switch ($request->get('downloads_filter_difficulties')) {
                case 1:
                    $qb->andWhere('rank.level BETWEEN 1 and 3');
                    $filters[] = "lvl 1 to 3";

                    break;
                case 2 :
                    $qb->andWhere('rank.level BETWEEN 4 and 7');
                    $filters[] = "lvl 4 to 7";
                    break;
                case 3 :
                    $qb->andWhere('rank.level BETWEEN 8 and 10');
                    $filters[] = "lvl 8 to 10";
                    break;
                case 6 :
                    $qb->andWhere('rank.level > 10');
                    $filters[] = "lvl over 10";
                    break;

            }
        }


        $categories = $request->get('downloads_filter_categories', null);
        if ($categories != null) {
            $qb->leftJoin('s.categoryTags', 't');

            $cats = [];
            foreach ($categories as $k => $v) {
                $qb->andWhere("t.id = :tag$k")->setParameter("tag$k", $v);
                $cats[] = $v;
            }
            $filters[] = "categories spécifiques";
        }

        if ($request->get('downloads_filter_order', null)) {
            switch ($request->get('downloads_filter_order')) {
                case 1:
                    $qb->orderBy('s.voteUp - s.voteDown', 'DESC');
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
                case 5 :
                    $qb->orderBy('s.downloads', 'DESC');

//                    $qb->addSelect("COUNT(dc.id) AS HIDDEN count_dl");
//                    $qb->groupBy("s.id");
//                    $qb->orderBy('count_dl', 'DESC');
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
                    $filters[] = "hide converted";
                    break;
                case 2 :
                    $qb->andWhere('s.converted = true');
                    $filters[] = "only converted";

                    break;
            }
        }

        if ($request->get('downloads_submitted_date', null)) {

            switch ($request->get('downloads_submitted_date')) {
                case 1:
                    $qb->andWhere('(s.lastDateUpload >= :last7days)')->setParameter('last7days', (new DateTime())->modify('-7 days'));
                    $filters[] = "last 7 days";
                    break;
                case 2 :
                    $qb->andWhere('(s.lastDateUpload >= :last15days)')->setParameter('last15days', (new DateTime())->modify('-15 days'));
                    $filters[] = "last 15 days";
                    break;
                case 3 :
                    $qb->andWhere('(s.lastDateUpload >= :last45days)')->setParameter('last45days', (new DateTime())->modify('-45 days'));
                    $filters[] = "last 45 days";
                    break;
            }
        }
        if ($request->get('not_downloaded', 0) > 0 && $this->isGranted('ROLE_USER')) {
            $qb->leftJoin("s.downloadCounters", 'download_counters')->addSelect("SUM(IF(download_counters.user = :user,1,0)) AS HIDDEN count_download_user")->andHaving("count_download_user = 0")->setParameter('user', $this->getuser());
            $filters[] = "not downloaded";
        }
        $qb->andWhere('s.moderated = true');

        //get the 'type' param (added for ajax search)
        $type = $request->get('type', null);
        //check if this is an ajax request
        $ajaxRequest = $type == 'ajax';
        //remove the 'type' parameter so pagination does not break
        if ($ajaxRequest) {
            $request->query->remove('type');
        }

        if ($request->get('search', null)) {
            $exp = explode(':', $request->get('search'));
            $filters[] = "search: \"" . $request->get('search') . "\"";

            switch ($exp[0]) {
                case 'mapper':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.levelAuthorName LIKE :search_string)')->setParameter('search_string', '%' . $exp[1] . '%');
                    }
                    break;
//                case 'category':
//                    if (count($exp) >= 1) {
//                        $qb->andWhere('(s.songCategory = :category)')
//                            ->setParameter('category', $exp[1] == "" ? null : $exp[1]);
//                    }
//                    break;
                case 'artist':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.authorName LIKE :search_string)')->setParameter('search_string', '%' . $exp[1] . '%');
                    }
                    break;
                case 'title':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.name LIKE :search_string)')->setParameter('search_string', '%' . $exp[1] . '%');
                    }
                    break;
                case 'desc':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.description LIKE :search_string)')->setParameter('search_string', '%' . $exp[1] . '%');
                    }
                    break;
                default:
                    $qb->andWhere('(s.name LIKE :search_string OR s.authorName LIKE :search_string OR s.description LIKE :search_string OR s.levelAuthorName LIKE :search_string)')->setParameter('search_string', '%' . $request->get('search', null) . '%');
            }
        }
        $qb->andWhere("s.isDeleted != true");

        if ($request->get('oneclick_dl')) {
            $songs = $qb->getQuery()->getResult();
            $list = new SongTemporaryList();

            $em = $this->getDoctrine()->getManager();
            foreach ($songs as $song) {
                $list->addSong($song);
            }
            $em->persist($list);
            $em->flush();

            return $this->redirect("ragnac://list/" . $list->getId());
        }

        if ($request->get('order_by') && in_array($request->get('order_by'), [
                's.lastDateUpload',
                'rating',
                's.downloads',
                's.name'
            ], true)) {
            $qb->orderBy($request->get('order_by'), $request->get('order_sort', 'asc'));
        }
        //$pagination = null;
        //if($ajaxRequest || $request->get('ppage1')) {
        $pagination = $paginationService->setDefaults($this->paginate)->process($qb, $request);

        //if this is an ajax request, send the HTML twig back to the calling fn in a json response
        if ($ajaxRequest) {
            //get the html from the twig
            $html = $this->renderView('songs/partial/song_row_div.html.twig', [
                'songs' => $pagination
            ]);
            //send the html back in json
            return new JsonResponse([
                "html" => $html
            ]);
        }

        if ($pagination->isPartial()) {
            return $this->render('songs/partial/song_row_div.html.twig', [
                'songs' => $pagination
            ]);
        }

        $categories = $categoryRepository->createQueryBuilder("c")->leftJoin("c.songs", 's')->where('c.isOnlyForAdmin != true')->andWhere("s.id is not null")->orderBy('c.label')->getQuery()->getResult();

        return $this->render('songs/song_library.html.twig', [
            'controller_name' => 'SongsController',
            'songs' => $pagination,
            'filters' => $filters,
            'categories' => $categories
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

        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $song->getId() . '.zip');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-type', "application/octet-stream");
        $response->headers->set('Content-Transfer-Encoding', "binary");
        $response->headers->set('Content-Length', filesize($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip"));

        return $response;
    }

    /**
     * @Route("/songs/download/{id}/{api}", name="song_download_api", defaults={"api"=null})
     */
    public function downloadApiKey(Request $request, Song $song, string $api, KernelInterface $kernel, DownloadService $downloadService, DownloadCounterRepository $downloadCounterRepository): Response
    {
        if (!$song->isModerated()) {
            return new Response("Not available now", 403);
        }
        $em = $this->getDoctrine()->getManager();
        $song->setDownloads($song->getDownloads() + 1);
        $em->flush();

        $fileContent = file_get_contents($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip");
        $downloadService->addOne($song, $api);

        $response = new Response($fileContent);

        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $song->getId() . '.zip');
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

        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $this->cleanName($song->getName()) . '.zip');
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
     * @Route("/toggle/{id}", name="diff_toggle_ranked", defaults={"slug"=null})
     */
    public function toggleRanked(Request $request, SongDifficulty $songDifficulty, ScoreService $scoreService)
    {
        if ($this->isGranted('ROLE_MODERATOR')) {
            $em = $this->getDoctrine()->getManager();
            $songDifficulty->setIsRanked(!$songDifficulty->isRanked());
            /** @var Score $score */
            foreach ($songDifficulty->getScores() as $score) {
                if (!$score->getRawPP() || $score->getRawPP() <= 0) {
                    $scoreService->archive($score);
                    $em->remove($score);
                }
            }
            $em->flush();
            return new JsonResponse(['result' => $songDifficulty->isRanked() ? '<i class="fas fa-star"></i> ranked' : '<i class="far fa-star"></i> not r.']);
        }
        return new Response('');
    }

    /**
     * @Route("/song/{slug}", name="song_detail", defaults={"slug"=null})
     */
    public function songDetail(Request $request, TranslatorInterface $translator, SongService $songService, PaginationService $paginationService, DiscordService $discordService, ?Song $song = null)
    {

        if ($song == null || (!$song->isModerated() && !$this->isGranted('ROLE_ADMIN') && $song->getUser() != $this->getUser()) || $song->getIsDeleted()) {
            $this->addFlash('warning', $translator->trans("This custom song is not available for now"));
            return $this->redirectToRoute('home');
        }
        $song->setViews($song->getViews() + 1);
        $feedback = new Vote();
        $feedback->setSong($song);
        $feedback->setHash($song->getNewGuid());
        $feedback->setUser($this->getUser());
        $feedbackForm = $this->createForm(VoteType::class, $feedback);

        $feedbackForm->handleRequest($request);
        $em = $this->getDoctrine()->getManager();

        if ($feedbackForm->isSubmitted() && $feedbackForm->isValid() && $this->getUser() != null) {
            $em->persist($feedback);
            $em->flush();
            $discordService->sendFeedback($feedback);
            $feedback = new Vote();
            $feedback->setSong($song);
            $feedback->setHash($song->getNewGuid());
            $feedback->setUser($this->getUser());
            $feedbackForm = $this->createForm(VoteType::class, $feedback);
            $this->addFlash("success", $translator->trans("Feedback sent!"));
        }
        $songService->emulatorFileDispatcher($song);
        $em->flush();

        $levels = [];
        foreach ($song->getSongDifficulties() as $difficulty) {
            $level = $difficulty->getDifficultyRank()->getLevel();
            $scores = $this->getDoctrine()->getRepository(Score::class)->createQueryBuilder('s')->select('s, MAX(s.score) AS HIDDEN max_score')->where('s.songDifficulty = :diff')->setParameter('diff', $difficulty)
//                ->setParameter('hash', $difficulty->getSong()->getNewGuid())
                ->groupBy('s.user')->addOrderBy('max_score', 'DESC');

            $pagination = $paginationService->setDefaults(20)->process($scores, $request);
            $levels [] = [
                "level" => $level,
                "difficulty" => $difficulty,
                "color" => $difficulty->getDifficultyRank()->getColor(),
                'scores' => $pagination
            ];
        }

        return $this->render('songs/detail.html.twig', [
            'song' => $song,
            'levels' => $levels,
            "feedbackForm" => $feedbackForm->createView()
        ]);
    }

    /**
     * @Route("/song/partial/last-played", name="last_songs_played")
     */
    public function lastSongsPlayed(Request $request, SongService $songService)
    {
        return $this->render('songs/partial/slider_cards.html.twig', ['songs' => $songService->getLastSongsPlayed(8)]);
    }

    /**
     * @Route("/song/partial/best", name="best_songs")
     */
    public function bestSongs(Request $request, SongService $songService)
    {
        return $this->render('songs/partial/slider_cards.html.twig', ['songs' => $songService->getLastSongsPlayed(8)]);
    }

    /**
     * @Route("/song/partial/last-added", name="last_songs_added")
     */
    public function lastSongsAdded(Request $request, SongService $songService)
    {
        return $this->render('songs/partial/slider_cards.html.twig', ['songs' => $songService->getLastSongsPlayed(8)]);
    }

    /**
     * @Route("/song/partial/preview/{id}", name="partial_preview_song")
     */
    public function partialPreview(Song $song)
    {
        return new JsonResponse(['response' => $this->renderView("songs/partial/preview_player.html.twig", ['song' => $song])]);
    }
}
