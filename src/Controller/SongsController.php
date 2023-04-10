<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\SongTemporaryList;
use App\Entity\Vote;
use App\Form\AddPlaylistFormType;
use App\Form\VoteType;
use App\Repository\DownloadCounterRepository;
use App\Repository\SongCategoryRepository;
use App\Repository\UtilisateurRepository;
use App\Service\DiscordService;
use App\Service\DownloadService;
use App\Service\GrantedService;
use App\Service\ScoreService;
use App\Service\SongService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class SongsController extends AbstractController
{
    private $paginate = 30;

    #[Route(path: '/song/detail/{id}', name: 'song_detail_old')]
    public function songDetailId(Request $request, Song $song)
    {
        return $this->redirectToRoute("song_detail", ['slug' => $song->getSlug()], 301);
    }


    /**
     * @param Request $request
     * @param Song $song
     * @param TranslatorInterface $translator
     * @return JsonResponse
     */
    #[Route(path: '/song/playlist/{id}', name: 'song_playlist')]
    public function formPlaylist(Request $request, ManagerRegistry $doctrine, Song $song, TranslatorInterface $translator)
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error"        => true,
                "errorMessage" => $translator->trans("You need an account!"),
                "response"     => $translator->trans("You need an account!"),
            ]);
        }

        if ($song == null) {
            return new JsonResponse([
                "error"        => true,
                "errorMessage" => $translator->trans("Song not found!"),
                "response"     => $translator->trans("Song not found!"),

            ]);
        }

        $form = $this->createForm(AddPlaylistFormType::class, $this->getUser(), [
            'attr' => [
                'class'    => "form ajax-form",
                'method'   => "post",
                "action"   => $this->generateUrl("song_playlist", ["id" => $song->getId()]),
                "data-url" => $this->generateUrl("song_playlist", ["id" => $song->getId()])
            ]
        ]);

        $form->handleRequest($request);
        $em = $doctrine->getManager();
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Playlist $playlist */
            $playlist = $form->get('playlist')->getData();
            if ($playlist == null) {
                $label = trim($form->get('newPlaylist')->getData());
                if ($label == null || empty($label)) {
                    return new JsonResponse([
                        "error"        => true,
                        "errorMessage" => $translator->trans("Playlist have to be named!"),
                        "response"     => $translator->trans("Playlist have to be named!"),

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
                        "error"        => true,
                        "errorMessage" => $translator->trans("Song already in playlist!"),
                        "response"     => $translator->trans("Song already in playlist!"),
                    ]);
                }
            }
            $playlist->addSong($song);
            $em->flush();
            return new JsonResponse([
                "error"        => false,
                "errorMessage" => "You need an account!",
                "response"     => "<div class='alert alert-success'>" . $translator->trans("Song added!") . "</div>",

            ]);
        }

        return new JsonResponse([
            "error"        => false,
            "errorMessage" => false,
            "response"     => $this->renderView("songs/partial/form_playlist.html.twig", [
                'form' => $form->createView(),
                'song' => $song,
            ]),
        ]);
    }

    /**
     * @param Request $request
     * @param SongCategoryRepository $categoryRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route(path: '/song-library', name: 'song_library')]
    public function library(Request $request, ManagerRegistry $doctrine, SongCategoryRepository $categoryRepository, PaginationService $paginationService): Response
    {
        $filters = [];
        $qb = $doctrine->getRepository(Song::class)->createQueryBuilder("s")->addSelect('s.voteUp - s.voteDown AS HIDDEN rating')->groupBy("s.id");

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

        if ($request->get('wip_maps', null)) {

            switch ($request->get('wip_maps')) {
                case 1:
                    //with
                    $filters[] = "display W.I.P.";
                    break;
                case 2 :
                    //only
                    $qb->andWhere('s.wip = true');
                    $filters[] = "only W.I.P.";
                    break;
                default:
                    $qb->andWhere('s.wip != true');
                    break;
            }
        } else {
            $qb->andWhere('s.wip != true');
        }

        if ($request->get('downloads_submitted_date', null)) {

            switch ($request->get('downloads_submitted_date')) {
                case 1:
                    $qb->andWhere('(s.programmationDate >= :last7days)')->setParameter('last7days', (new DateTime())->modify('-7 days'));
                    $filters[] = "last 7 days";
                    break;
                case 2 :
                    $qb->andWhere('(s.programmationDate >= :last15days)')->setParameter('last15days', (new DateTime())->modify('-15 days'));
                    $filters[] = "last 15 days";
                    break;
                case 3 :
                    $qb->andWhere('(s.programmationDate >= :last45days)')->setParameter('last45days', (new DateTime())->modify('-45 days'));
                    $filters[] = "last 45 days";
                    break;
            }
        }
        if ($request->get('not_downloaded', 0) > 0 && $this->isGranted('ROLE_USER')) {
            $qb->leftJoin("s.downloadCounters", 'download_counters')->addSelect("SUM(IF(download_counters.user = :user,1,0)) AS HIDDEN count_download_user")->andHaving("count_download_user = 0")->setParameter('user', $this->getuser());
            $filters[] = "not downloaded";
        }

        $qb->andWhere('s.moderated = true');
        $qb->andWhere('s.active = true')
        ->andWhere('(s.programmationDate <= :now OR s.programmationDate IS NULL)')
->setParameter('now', new DateTime());
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

            $em = $doctrine->getManager();
            foreach ($songs as $song) {
                $list->addSong($song);
            }
            $em->persist($list);
            $em->flush();

            return $this->redirect("ragnac://list/" . $list->getId());
        }

        switch ($request->get('order_by', null)) {
            case 'downloads':
                $qb->orderBy("s.downloads", $request->get('order_sort', 'asc') == "asc" ? "asc" : "desc");
                break;
            case 'upload_date':
                $qb->orderBy("s.programmationDate", $request->get('order_sort', 'asc') == "asc" ? "asc" : "desc");
                break;
            case 'name':
                $qb->orderBy("s.name", $request->get('order_sort', 'asc') == "asc" ? "asc" : "desc");
                break;
            case 'rating':
                $qb->orderBy("rating", $request->get('order_sort', 'asc') == "asc" ? "asc" : "desc");
                break;
            default:
                $qb->orderBy("s.programmationDate", "DESC");
                break;
        }

        $pagination = $paginationService->setDefaults($this->paginate)->process($qb, $request);

        $categories = $categoryRepository->createQueryBuilder("c")->leftJoin("c.songs", 's')->where('c.isOnlyForAdmin != true')->andWhere("s.id is not null")->orderBy('c.label')->getQuery()->getResult();

        return $this->render('songs/song_library.html.twig', [
            'controller_name' => 'SongsController',
            'songs'           => $pagination,
            'filters'         => $filters,
            'categories'      => $categories
        ]);
    }

    #[Route(path: '/songs/download/{id}', name: 'song_download')]
    public function download(Request $request, ManagerRegistry $doctrine, Song $song, KernelInterface $kernel, DownloadService $downloadService, DownloadCounterRepository $downloadCounterRepository)
    {
        if (!$song->isModerated() || $song->getProgrammationDate() == null || $song->getProgrammationDate() > new DateTime()) {
            return new Response("Not available now", 403);
        }
        $em = $doctrine->getManager();
        $song->setDownloads($song->getDownloads() + 1);
        $em->flush();

        $downloadService->addOne($song);
        return $this->RestrictedDownload($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip", $song->getId() . ".zip");

//        $response = new Response($fileContent);
//        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $song->getId() . '.zip');
//        $response->headers->set('Content-Disposition', $disposition);
//        $response->headers->set('Content-type', "application/octet-stream");
//        $response->headers->set('Content-Transfer-Encoding', "binary");
//        $response->headers->set('Content-Length', filesize($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip"));
//        return $response;

    }

    #[Route(path: '/songs/download/{id}/{api}', name: 'song_download_api', defaults: ['api' => null])]
    public function downloadApiKey(GrantedService $grantedService, ManagerRegistry $doctrine, Song $song, string $api, KernelInterface $kernel, DownloadService $downloadService, UtilisateurRepository $utilisateurRepository)
    {
        if (!$song->isModerated() || $song->getProgrammationDate() == null || $song->getProgrammationDate() > new DateTime()) {
            return new Response("Not available now", 403);
        }
        $em = $doctrine->getManager();
        $song->setDownloads($song->getDownloads() + 1);
        $em->flush();
        $user = $utilisateurRepository->findOneBy(["apiKey" => $api]);
        if ($user != null) {
            $downloadService->addOne($song, $api);
        }
        if ($grantedService->isGranted($user, 'ROLE_PREMIUM_LVL1')) {
            $fileContent = file_get_contents($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip");

            $response = new Response($fileContent);
            $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $song->getId() . '.zip');
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-type', "application/octet-stream");
            $response->headers->set('Content-Transfer-Encoding', "binary");
            $response->headers->set('Content-Length', filesize($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip"));

            return $response;
        } else {
            $file = $kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip"; // Nom du fichier
            return $this->RestrictedDownload($file, $song->getId() . ".zip");
        }
    }

    #[Route(path: '/songs/ddl/{id}', name: 'song_direct_download')]
    public function directDownload(Song $song, ManagerRegistry $doctrine, KernelInterface $kernel, DownloadService $downloadService)
    {
        if (!$song->isModerated() || $song->getProgrammationDate() == null || $song->getProgrammationDate() > new DateTime()) {
            return new Response("Not available now", 403);
        }
        $em = $doctrine->getManager();
        $song->setDownloads($song->getDownloads() + 1);
        $em->flush();
        $downloadService->addOne($song);

        if ($this->isGranted('ROLE_PREMIUM_LVL1')) {
            $fileContent = file_get_contents($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip");
            $response = new Response($fileContent);
            $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $this->cleanName($song->getName()) . '.zip');
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-type', "application/octet-stream");
            $response->headers->set('Content-Transfer-Encoding', "binary");
            $response->headers->set('Content-Length', filesize($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip"));
            return $response;

        } else {
            $file = $kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip"; // Nom du fichier
            return $this->RestrictedDownload($file, $this->cleanName($song->getName()) . ".zip");
        }
    }

    private function cleanName(?string $getName)
    {
        return preg_replace('/[^a-zA-Z]/i', '', $getName);
    }

    #[Route(path: '/toggle/{id}', name: 'diff_toggle_ranked', defaults: ['slug' => null])]
    public function toggleRanked(Request $request, ManagerRegistry $doctrine, SongDifficulty $songDifficulty, ScoreService $scoreService)
    {
        if ($this->isGranted('ROLE_MODERATOR')) {
            $em = $doctrine->getManager();
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

    #[Route(path: '/song/{slug}', name: 'song_detail', defaults: ['slug' => null])]
    public function songDetail(Request $request, ManagerRegistry $doctrine, TranslatorInterface $translator, SongService $songService, PaginationService $paginationService, DiscordService $discordService, ?Song $song = null)
    {

        if ($song == null || $song->getProgrammationDate() == null || $song->getProgrammationDate() >= new DateTime() || (!$song->isModerated() && !$this->isGranted('ROLE_ADMIN') && $song->getUser() != $this->getUser()) || $song->getIsDeleted()) {
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
        $em = $doctrine->getManager();

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
            $scores = $doctrine->getRepository(Score::class)->createQueryBuilder('s')->select('s, MAX(s.score) AS HIDDEN max_score')->where('s.songDifficulty = :diff')->setParameter('diff', $difficulty)
//                ->setParameter('hash', $difficulty->getSong()->getNewGuid())
                               ->groupBy('s.user')->addOrderBy('max_score', 'DESC');

            $pagination = $paginationService->setDefaults(20)->process($scores, $request);
            $levels [] = [
                "level"      => $level,
                "difficulty" => $difficulty,
                "color"      => $difficulty->getDifficultyRank()->getColor(),
                'scores'     => $pagination
            ];
        }

        return $this->render('songs/detail.html.twig', [
            'song'         => $song,
            'levels'       => $levels,
            "feedbackForm" => $feedbackForm->createView()
        ]);
    }

    #[Route(path: '/song/partial/last-played', name: 'last_songs_played')]
    public function lastSongsPlayed(Request $request, SongService $songService)
    {
        return $this->render('songs/partial/slider_cards.html.twig', ['songs' => $songService->getLastSongsPlayed(8)]);
    }

    #[Route(path: '/song/partial/best', name: 'best_songs')]
    public function bestSongs(Request $request, SongService $songService)
    {
        return $this->render('songs/partial/slider_cards.html.twig', ['songs' => $songService->getLastSongsPlayed(8)]);
    }

    #[Route(path: '/song/partial/last-added', name: 'last_songs_added')]
    public function lastSongsAdded(Request $request, SongService $songService)
    {
        return $this->render('songs/partial/slider_cards.html.twig', ['songs' => $songService->getLastSongsPlayed(8)]);
    }

    #[Route(path: '/song/partial/preview/{id}', name: 'partial_preview_song')]
    public function partialPreview(Song $song)
    {
        return new JsonResponse(['response' => $this->renderView("songs/partial/preview_player.html.twig", ['song' => $song])]);
    }

    private function RestrictedDownload(string $file, string $filename)
    {
        if (file_exists($file) && is_file($file)) {
            $response = new StreamedResponse();
            $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename);
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-type', "application/octet-stream");
            $response->headers->set('Content-Transfer-Encoding', "binary");
            $response->headers->set('Content-Length', filesize($file));
            $response->setCallback(function () use ($file, $filename) {
                $speed = 1000; // i.e. 50 kb/s temps de telechargement
                $fd = fopen($file, "r");
                $octet = round($speed * 1024);
                while (!feof($fd)) {
                    echo fread($fd, $octet); // $speed kilobytes (Kb)
                    flush();
                    sleep(1);
                }
                fclose($fd);
            });
            return $response;
        }
        return new Response("ERROR", 400);
    }
}