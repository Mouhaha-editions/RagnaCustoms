<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Repository\DifficultyRankRepository;
use App\Repository\SongRepository;
use App\Service\DiscordService;
use App\Service\SongService;
use Exception;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

class AdminSongController extends AbstractController
{

    /**
     * @Route("/admin/moderation", name="moderate_song")
     */
    public function moderateSongList(request $request, SongRepository $songRepository, PaginationService $paginationService)
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
        if ($request->get('search', null)) {
            $qb->andWhere('(s.name LIKE :search_string OR s.authorName LIKE :search_string OR s.levelAuthorName LIKE :search_string)')
                ->setParameter('search_string', '%' . $request->get('search', null) . '%');
        }
        if ($request->get('moderated', null)) {
            switch ($request->get('moderated')) {
                case 1:
                    $qb->andWhere('(s.moderated = true)');
                    break;
                case 2 :
                    $qb->andWhere('(s.moderated = false)');
                    break;
            }
        }
        $qb->orderBy('s.createdAt', 'DESC');
        $pagination = $paginationService->setDefaults(50)->process($qb, $request);
        if ($pagination->isPartial()) {
            return $this->render("admin/song/moderation_song_row.html.twig", ['songs' => $pagination]);
        }
        return $this->render("admin/song/moderation.html.twig", ['songs' => $pagination]);
    }

    /**
     * @Route("/admin/moderation/{id}", name="moderate_song_ajax")
     */
    public function moderateSongAjaxList(request $request, Song $song, DiscordService $discordService)
    {
        $em = $this->getDoctrine()->getManager();
        $song->setModerated(true);
        $em->flush();

        if ($song->isModerated()) {
            $discordService->sendNewSongMessage($song);
        }
        return new JsonResponse([
            'error' => false,
            'errorMessage' => false,
            'result' => $this->renderView("upload_song/partial/button_moderation.html.twig", ["song" => $song]),

        ]);
    }

    /**
     * @Route("/admin/song", name="admin_song")
     */
    public function index(Request $request, PaginationService $paginationService): Response
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
        return $this->render('admin_song/index.html.twig', [
            'songs' => $pagination
        ]);

    }

    /**
     * @Route("/admin/song/emulate/all", name="admin_song_emulate_all")
     */
    public function emulateAll(KernelInterface $kernel, SongRepository $songRepository, SongService $songService)
    {
        foreach ($songRepository->findAll() as $song) {
            $songService->emulatorFileDispatcher($song, true);
        }

        return $this->redirectToRoute('admin_song');
    }

    /**
     * @Route("/admin/song/emulate/{id}", name="admin_song_emulate")
     */
    public function emulate(Song $song, KernelInterface $kernel, DifficultyRankRepository $difficultyRankRepository, SongService $songService)
    {
        $songService->emulatorFileDispatcher($song, true);
        return $this->redirectToRoute('admin_song');
    }

    public function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }
    /**
     * @Route("/admin/organize/all", name="admin_organize_all")
     */
    public function reorganizeAll(SongRepository $songRepository, KernelInterface $kernel)
    {
        $zip = new ZipArchive();
        foreach ($songRepository->findAll() as $song) {
            $finalFolder = $kernel->getProjectDir() . "/public/songs-files/";
            $theZip = $finalFolder . $song->getId() . ".zip";
            $infolder = strtolower(preg_replace('/[^a-zA-Z]/', '', $song->getName()));
            if ($zip->open($theZip) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $newfilename = $filename = ($zip->getNameIndex($i));
                    if (preg_match("/Info\.dat/", $filename, $matches)) {
                        $newfilename = strtolower($filename);
                    }
                    $x = explode('/', $newfilename);

                    $zip->renameName($filename, $infolder . "/" . $x[count($x) - 1]);

                }
                $zip->close();
            }
        }

        return new Response("OK");
    }

    /**
     * @Route("/admin/organize/{id}", name="admin_organize")
     */
    public function reorganize(Song $song, KernelInterface $kernel)
    {
        $zip = new ZipArchive();
        $finalFolder = $kernel->getProjectDir() . "/public/songs-files/";
        $theZip = $finalFolder . $song->getId() . ".zip";
        $infolder = strtolower(preg_replace('/[^a-zA-Z]/', '', $song->getName()));
        if ($zip->open($theZip) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $newfilename = $filename = ($zip->getNameIndex($i));
                if (preg_match("/Info\.dat/", $filename, $matches)) {
                    $newfilename = strtolower($filename);
                }
                $x = explode('/', $newfilename);

                $zip->renameName($filename, $infolder . "/" . $x[count($x) - 1]);

            }
            $zip->close();
        }
        return new Response("OK");
    }


}
