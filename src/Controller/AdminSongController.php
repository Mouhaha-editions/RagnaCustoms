<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Repository\DifficultyRankRepository;
use App\Repository\SongRepository;
use App\Service\DiscordService;
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
        $song->setModerated(!$song->isModerated());
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
     * @Route("/admin/song/reload/{id}", name="admin_song_reload")
     */
    public function reload(Song $song, KernelInterface $kernel, DifficultyRankRepository $difficultyRankRepository)
    {
        $em = $this->getDoctrine()->getManager();
        $finalFolder = $kernel->getProjectDir() . "/public/songs-files/";

        $folder = $kernel->getProjectDir() . "/public/tmp-song/";
        $unzipFolder = $folder . uniqid();
        mkdir($unzipFolder);
        $zip = new ZipArchive();
        $theZip = $finalFolder . $song->getId() . ".zip";

        $allowedFiles = [
            'preview.ogg',
            'info.dat'
        ];
        if ($zip->open($theZip) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $elt = $zip->getFromIndex($i);
                $exp = explode("/", $filename);
                if (end($exp) != "") {
                    $fileinfo = pathinfo($filename);
                    file_put_contents($unzipFolder . "/" . $fileinfo['basename'], $elt);
                }
            }
            $zip->close();
        }
        try {
            $file = $unzipFolder . "/info.dat";
            if (!file_exists($file)) {
                $file = $unzipFolder . "/Info.dat";
                if (!file_exists($file)) {
                    $this->addFlash('danger', "The file seems to not be valid, at least info.dat is missing.");
                    $this->rrmdir($unzipFolder);
                    return $this->redirectToRoute("admin_song");
                }
            }
            $json = json_decode(file_get_contents($file));
            $allowedFiles[] = $json->_coverImageFilename;
            $allowedFiles[] = $json->_songFilename;

        } catch (Exception $e) {
            $this->addFlash('danger', "The file seems to not be valid, at least info.dat is missing.");
            $this->rrmdir($unzipFolder);
            return $this->redirectToRoute("upload_song");
        }

        $song->setVersion($json->_version);
        $song->setName($json->_songName);
        $song->setSubName($json->_songSubName);
        $song->setAuthorName($json->_songAuthorName);
        $song->setLevelAuthorName($json->_levelAuthorName);
        $song->setBeatsPerMinute($json->_beatsPerMinute);
        $song->setShuffle($json->_shuffle);
        $song->setShufflePeriod($json->_shufflePeriod);
        $song->setPreviewStartTime($json->_previewStartTime);
        $song->setPreviewDuration($json->_previewDuration);
        $song->setApproximativeDuration($json->_songApproximativeDuration);
        $song->setApproximativeDuration($json->_songApproximativeDuration);
        $song->setFileName($json->_songFilename);
        $song->setCoverImageFileName($json->_coverImageFilename);
        $song->setEnvironmentName($json->_environmentName);
        $song->setModerated($this->getUser()->isCertified() ?: false);

        $em->persist($song);
        foreach ($song->getSongDifficulties() as $difficulty) {
            $em->remove($difficulty);
        }
        $song->setTotalVotes(null);
        $song->setCountVotes(null);


        foreach (($json->_difficultyBeatmapSets[0])->_difficultyBeatmaps as $difficulty) {
            $diff = new SongDifficulty();
            $diff->setSong($song);
            $diff->setDifficultyRank($difficultyRankRepository->findOneBy(["level" => $difficulty->_difficultyRank]));
            $diff->setDifficulty($difficulty->_difficulty);
            $diff->setNoteJumpMovementSpeed($difficulty->_noteJumpMovementSpeed);
            $diff->setNoteJumpStartBeatOffset($difficulty->_noteJumpStartBeatOffset);
            $em->persist($diff);
            $allowedFiles[] = $difficulty->_beatmapFilename;
            $file = $difficulty->_beatmapFilename;

            $file = $unzipFolder . "/" . $file;
            $json2 = json_decode(file_get_contents($file));
            $diff->setNotesCount(count($json2->_notes));
            $diff->setNotePerSecond($diff->getNotesCount() / $song->getApproximativeDuration());

        }

        $em->flush();


        /** @var UploadedFile $file */
        $patterns_flattened = strtolower(implode('|', $allowedFiles));
        $zip = new ZipArchive();
        if ($zip->open($theZip) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = ($zip->getNameIndex($i));
                if (!preg_match('/' . $patterns_flattened . '/', strtolower($filename), $matches) || preg_match('/autosaves/', strtolower($filename), $matches)) {
                    $zip->deleteName($filename);
                }
            }
            $zip->close();
        }
        $this->rrmdir($unzipFolder);

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
}
