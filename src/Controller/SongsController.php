<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\Score;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\SongTemporaryList;
use App\Entity\Vote;
use App\Enum\ENotification;
use App\Form\AddPlaylistFormType;
use App\Form\VoteType;
use App\Repository\DownloadCounterRepository;
use App\Repository\SongCategoryRepository;
use App\Repository\SongRepository;
use App\Repository\UtilisateurRepository;
use App\Service\DiscordService;
use App\Service\DownloadService;
use App\Service\GrantedService;
use App\Service\NotificationService;
use App\Service\ScoreService;
use App\Service\SearchService;
use App\Service\SongService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;


class SongsController extends AbstractController
{
    private $paginate = 30;

    public function __construct(private readonly SongService $songService)
    {
    }

    #[Route(path: '/song/detail/{id}', name: 'song_detail_old')]
    public function songDetailId(Request $request, Song $song)
    {
        return $this->redirectToRoute("song_detail", ['slug' => $song->getSlug()], 301);
    }

    /**
     * @param Request $request
     * @param Song $song
     * @param TranslatorInterface $translator
     *
     * @return JsonResponse
     */
    #[Route(path: '/song/playlist/{id}', name: 'song_playlist')]
    public function formPlaylist(
        Request $request,
        ManagerRegistry $doctrine,
        Song $song,
        TranslatorInterface $translator
    ) {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account!"),
                "response" => $translator->trans("You need an account!"),
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
                "data-url" => $this->generateUrl("song_playlist", ["id" => $song->getId()]),
            ],
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
                "errorMessage" => "You need an account!",
                "response" => "<div class='alert alert-success'>".$translator->trans("Song added!")."</div>",

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

    #[Route(path: '/song-library', name: 'song_library')]
    public function library(
        Request $request,
        ManagerRegistry $doctrine,
        SongCategoryRepository $categoryRepository,
        PaginationService $paginationService,
        SearchService $searchService
    ): Response {
        $qb = $doctrine
            ->getRepository(Song::class)
            ->createQueryBuilder('song')
            ->addSelect('song.voteUp - song.voteDown AS HIDDEN rating')
            ->groupBy("song.id");

        $qb->leftJoin('song.songDifficulties', 'song_difficulties');

        $filters = $searchService->baseSearchQb($qb, $request);

        if ($request->get('oneclick_dl')) {
            $songs = $qb->getQuery()->getResult();
            $list = new SongTemporaryList();

            $em = $doctrine->getManager();
            foreach ($songs as $song) {
                $list->addSong($song);
            }
            $em->persist($list);
            $em->flush();

            return $this->redirect("ragnac://list/".$list->getId());
        }

        $pagination = $paginationService->setDefaults($this->paginate)->process($qb, $request);

        $categories = $categoryRepository
            ->createQueryBuilder("c")
            ->leftJoin("c.songs", 's')
            ->where('c.isOnlyForAdmin != true')
            ->andWhere("s.id is not null")
            ->orderBy('c.label')
            ->getQuery()
            ->getResult();

        return $this->render('songs/song_library.html.twig', [
            'controller_name' => 'SongsController',
            'songs' => $pagination,
            'filters' => $filters,
            'categories' => $categories,
        ]);
    }

    #[Route(path: '/songs/download/{id}', name: 'song_download')]
    public function download(
        Request $request,
        ManagerRegistry $doctrine,
        string $id,
        KernelInterface $kernel,
        DownloadService $downloadService,
        SongRepository $songRepository,
        DownloadCounterRepository $downloadCounterRepository
    ): StreamedResponse|Response {

        if (is_numeric($id)) {
            $song = $songRepository->find($id);

            if (!$song || $song->isPrivate()) {
                return new Response("Not available now", 403);
            }
        } else {
            $song = $songRepository->findOneBy(['privateLink' => $id]);
        }

        if (!$song->isModerated() || $song->getProgrammationDate() == null || $song->getProgrammationDate(
            ) > new DateTime()) {
            return new Response("Not available now", 403);
        }
        $em = $doctrine->getManager();
        $song->setDownloads($song->getDownloads() + 1);
        $em->flush();

        $downloadService->addOne($song);
        $this->reformatSubFolderName($song, $kernel);

        return $this->RestrictedDownload(
            $kernel->getProjectDir()."/public/songs-files/".$song->getId().".zip",
            ($song->isPrivate() ? $song->getPrivateLink():$song->getId()).".zip"
        );

//        $response = new Response($fileContent);
//        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $song->getId() . '.zip');
//        $response->headers->set('Content-Disposition', $disposition);
//        $response->headers->set('Content-type', "application/octet-stream");
//        $response->headers->set('Content-Transfer-Encoding', "binary");
//        $response->headers->set('Content-Length', filesize($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip"));
//        return $response;

    }

    private function reformatSubFolderName(Song $song, KernelInterface $kernel)
    {
        $zipFile = $kernel->getProjectDir()."/public/songs-files/".$song->getId().".zip";
        $oldFolderName = $this->slugify($song->getName());
        $newFolderName = $this->slugify($song->getName()).$this->slugify($song->getAuthorName()).$this->slugify(
                $song->getLevelAuthorName()
            );

        $zip = new ZipArchive();
        if ($zip->open($zipFile) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (preg_match('#^'.$oldFolderName.'\/(.*)$#', $filename)) {
                    $zip->renameIndex(
                        $i,
                        preg_replace('#^'.$oldFolderName.'\/(.*)$#', $newFolderName.'/$1', $filename)
                    );
                }
            }
            $zip->close();
        }
    }

    private function slugify(string $text): string
    {
        $pattern = '/[^a-zA-Z]/';
        $text = preg_replace($pattern, '', $text);

        return strtolower($text);
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

    #[Route(path: '/songs/download/{id}/{api}', name: 'song_download_api', defaults: ['api' => null])]
    public function downloadApiKey(
        GrantedService $grantedService,
        ManagerRegistry $doctrine,
        string $id,
        string $api,
        KernelInterface $kernel,
        DownloadService $downloadService,
        SongRepository $songRepository,
        UtilisateurRepository $utilisateurRepository
    ) {
        if (is_numeric($id)) {
            $song = $songRepository->find($id);

            if (!$song || $song->isPrivate()) {
                return new Response("Not available now", 403);
            }
        } else {
            $song = $songRepository->findOneBy(['privateLink' => $id]);
        }

        if (!$song || !$song->isModerated() || $song->getProgrammationDate() == null || $song->getProgrammationDate(
            ) > new DateTime()) {
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
            $fileContent = file_get_contents(
                $kernel->getProjectDir()."/public/songs-files/".$song->getId().".zip"
            );

            $response = new Response($fileContent);
            $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $song->getId().'.zip');
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-type', "application/octet-stream");
            $response->headers->set('Content-Transfer-Encoding', "binary");
            $response->headers->set(
                'Content-Length',
                filesize($kernel->getProjectDir()."/public/songs-files/".$song->getId().".zip")
            );

            return $response;
        } else {
            $file = $kernel->getProjectDir()."/public/songs-files/".$song->getId().".zip"; // Nom du fichier

            return $this->RestrictedDownload($file, ($song->isPrivate() ? $song->getPrivateLink() : $song->getId()).".zip");
        }
    }

    #[Route(path: '/songs/ddl/{id}', name: 'song_direct_download')]
    public function directDownload(
        string $id,
        ManagerRegistry $doctrine,
        KernelInterface $kernel,
        DownloadService $downloadService,
        SongRepository $songRepository
    ) {
        if (is_numeric($id)) {
            $song = $songRepository->find($id);
        } else {
            $song = $songRepository->findOneBy(['privateLink' => $id]);
        }

        if ($song && $song->isPrivate() && is_numeric($id)) {
            return new Response("Not available now", 404);
        }

        if ( !$song || !$song->isModerated()
            || $song->getProgrammationDate() == null
            || $song->getProgrammationDate() > new DateTime()) {
            if ($this->isGranted('ROLE_ADMIN') || ($song && $song->getMappers()->contains($this->getUser()))) {

            } else {
                return new Response("Not available now", 403);
            }
        }


        $em = $doctrine->getManager();
        $song->setDownloads($song->getDownloads() + 1);
        $em->flush();
        $downloadService->addOne($song);
        $this->reformatSubFolderName($song, $kernel);
        if ($this->isGranted('ROLE_PREMIUM_LVL1')) {
            $fileContent = file_get_contents(
                $kernel->getProjectDir()."/public/songs-files/".$song->getId().".zip"
            );
            $response = new Response($fileContent);
            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $this->cleanName($song->getName()).'.zip'
            );
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-type', "application/octet-stream");
            $response->headers->set('Content-Transfer-Encoding', "binary");
            $response->headers->set(
                'Content-Length',
                filesize($kernel->getProjectDir()."/public/songs-files/".$song->getId().".zip")
            );

            return $response;
        } else {
            $file = $kernel->getProjectDir()."/public/songs-files/".$song->getId().".zip"; // Nom du fichier

            return $this->RestrictedDownload($file, $this->cleanName($song->getName()).".zip");
        }
    }

    private function cleanName(?string $getName)
    {
        $cyr = array(
            'а',
            'б',
            'в',
            'г',
            'д',
            'е',
            'ё',
            'ж',
            'з',
            'и',
            'й',
            'к',
            'л',
            'м',
            'н',
            'о',
            'п',
            'р',
            'с',
            'т',
            'у',
            'ф',
            'х',
            'ц',
            'ч',
            'ш',
            'щ',
            'ъ',
            'ы',
            'ь',
            'э',
            'ю',
            'я',
            'А',
            'Б',
            'В',
            'Г',
            'Д',
            'Е',
            'Ж',
            'З',
            'И',
            'Й',
            'К',
            'Л',
            'М',
            'Н',
            'О',
            'П',
            'Р',
            'С',
            'Т',
            'У',
            'Ф',
            'Х',
            'Ц',
            'Ч',
            'Ш',
            'Щ',
            'Ъ',
            'Ы',
            'Ь',
            'Э',
            'Ю',
            'Я',
        );
        $lat = array(
            'a',
            'b',
            'v',
            'g',
            'd',
            'e',
            'io',
            'zh',
            'z',
            'i',
            'y',
            'k',
            'l',
            'm',
            'n',
            'o',
            'p',
            'r',
            's',
            't',
            'u',
            'f',
            'h',
            'ts',
            'ch',
            'sh',
            'sht',
            'a',
            'i',
            'y',
            'e',
            'yu',
            'ya',
            'A',
            'B',
            'V',
            'G',
            'D',
            'E',
            'Zh',
            'Z',
            'I',
            'Y',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'R',
            'S',
            'T',
            'U',
            'F',
            'H',
            'Ts',
            'Ch',
            'Sh',
            'Sht',
            'A',
            'Y',
            'Yu',
            'Ya',
        );

        $getName = str_replace($cyr, $lat, $getName);

        return preg_replace('/[^a-zA-Z]/i', '', $getName);
    }

    #[Route(path: '/toggle/{id}', name: 'diff_toggle_ranked', defaults: ['slug' => null])]
    public function toggleRanked(
        Request $request,
        ManagerRegistry $doctrine,
        SongDifficulty $songDifficulty,
        ScoreService $scoreService
    ) {
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

            return new JsonResponse(
                [
                    'result' => $songDifficulty->isRanked(
                    ) ? '<i class="fas fa-star"></i> ranked' : '<i class="far fa-star"></i> not r.',
                ]
            );
        }

        return new Response('');
    }



    #[Route(path: '/random', name: 'random_song')]
    public function songRandom(
        SongRepository $songRepository
    ): RedirectResponse|Response
    {
       $song  = $songRepository->createQueryBuilder('song')->orderBy('RAND()')
           ->setFirstResult(0)
           ->setMaxResults(1)
           ->where('song.active = true')
           ->AndWhere('song.active = true')
           ->AndWhere('song.isPrivate = false')
           ->AndWhere('song.programmationDate < NOW()')
           ->AndWhere('song.isDeleted = false')
           ->AndWhere('song.isNotificationDone = 1')
           ->AndWhere('song.wip = 0')
           ->getQuery()
           ->getOneOrNullResult();

        return $this->redirectToRoute('song_detail', ['slug' => $song->getSlug()]);
    }


    #[Route(path: '/secure/{privateLink}', name: 'secure_song')]
    public function songDetailSecure(
        Request $request,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        SongService $songService,
        PaginationService $paginationService,
        DiscordService $discordService,
        NotificationService $notificationService,
        SongRepository $songRepository,
        string $privateLink
    ): RedirectResponse|Response
    {
        $song = $songRepository->findOneBy(['privateLink' => $privateLink]);
        return $this->songDetail($request, $doctrine, $translator, $songService, $paginationService, $discordService, $notificationService, $song);
    }


    #[Route(path: '/song/{slug}', name: 'song_detail', defaults: ['slug' => null])]
    public function songDetail(
        Request $request,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        SongService $songService,
        PaginationService $paginationService,
        DiscordService $discordService,
        NotificationService $notificationService,
        ?Song $song = null
    ): RedirectResponse|Response {

        if ($song && $song->isPrivate() && $request->attributes->get('_route') == 'song_detail' && !$song->getMappers()->contains($this->getUser())) {
            return $this->redirectToRoute('home');
        }


        if ($song == null
            || $song->getIsDeleted()
            || (
                ($song->getProgrammationDate() == null
                    || $song->getProgrammationDate() >= new DateTime()
                    || !$song->isModerated())
                && !$this->isGranted('ROLE_ADMIN')
                && !$song->getMappers()->contains($this->getUser())
            )) {
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

            foreach ($song->getMappers() as $mapper) {
                if ($mapper->hasNotificationPreference(ENotification::Mapper_new_feedback)) {
                    $notificationService->send(
                        $mapper,
                        'You got a new feedback on <a href="'.$this->generateUrl(
                            'song_detail',
                            ['slug' => $song->getSlug()]
                        ).'">'.$song->getName()."</a>"
                    );
                }
            }

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
            $scores = $doctrine
                ->getRepository(Score::class)
                ->createQueryBuilder('s')
                ->select('s, MAX(s.score) AS HIDDEN max_score')
                ->where('s.songDifficulty = :diff')
                ->andWhere('s.plateform IN (:type)')
                ->setParameter('diff', $difficulty)
                ->groupBy('s.user')
                ->addOrderBy('max_score', 'DESC')
                ->setParameter('type', WanadevApiController::VR_PLATEFORM);
            $scoresFlat = clone $scores;
            $scoresFlat->andWhere('s.plateform IN (:type)')
                ->setParameter('type', WanadevApiController::FLAT_PLATEFORM);

            $scoresOKOD = clone $scores;
            $scoresOKOD->andWhere('s.plateform IN (:type)')
                ->setParameter('type', WanadevApiController::OKOD_PLATEFORM);

            $pagination = $paginationService->setDefaults(30)->process($scores, $request);
            $paginationFlat = $paginationService->setDefaults(30)->process($scoresFlat, $request);
            $paginationOKOD = $paginationService->setDefaults(30)->process($scoresOKOD, $request);

            $levels [] = [
                "level" => $level,
                "difficulty" => $difficulty,
                "color" => $difficulty->getDifficultyRank()->getColor(),
                'scores' => $pagination,
                'scoresFlat' => $paginationFlat,
                'scoresOKOD' => $paginationOKOD,

            ];

            if ($pagination->isPartial()) {
                return $this->render('songs/partial/leaderboard.html.twig', [
                    'level' => array_pop($levels),
                    'type' => 'scores',
                ]);
            }

            if ($paginationFlat->isPartial()) {
                return $this->render('songs/partial/leaderboard.html.twig', [
                    'level' => array_pop($levels),
                    'type' => 'scoresFlat',
                ]);
            }

            if ($paginationOKOD->isPartial()) {
                return $this->render('songs/partial/leaderboard.html.twig', [
                    'level' => array_pop($levels),
                    'type' => 'scoresOKOD',
                ]);
            }
        }

        return $this->render('songs/detail.html.twig', [
            'song' => $song,
            'levels' => $levels,
            "feedbackForm" => $feedbackForm->createView(),
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
        return new JsonResponse(
            ['response' => $this->renderView("songs/partial/preview_player.html.twig", ['song' => $song])]
        );
    }

    public function __invoke(): array
    {

        return [
            'countSongs' => $this->songService->count(),
            'topRated' => $this->songService->getTopRated(),
            'lastSongs' => $this->songService->getLastSongs(),
        ];
    }
}
