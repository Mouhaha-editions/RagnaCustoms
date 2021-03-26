<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Repository\DifficultyRankRepository;
use App\Repository\SongRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

class UploadSongController extends AbstractController
{
    /**
     * @Route("/upload/song", name="upload_song")
     */
    public function index(KernelInterface $kernel, SongRepository $songRepository, DifficultyRankRepository $difficultyRankRepository): Response
    {
        $em = $this->getDoctrine()->getManager();

        $folder = $kernel->getProjectDir() . "/public/songs/Swedish Pagans/";

        $json = json_decode(file_get_contents($folder . "info.dat"));
        $song = $songRepository->findBy([
            "name" => $json->_songName,
            "authorName" => $json->_songAuthorName
        ]);
        if ($song != null) {
            $this->addFlash("danger", "Cette musique est déjà dans notre catalogue.");
            return $this->redirectToRoute("home");
        }
        $song = new Song();
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
        $em->persist($song);
        foreach (($json->_difficultyBeatmapSets[0])->_difficultyBeatmaps as $difficulty) {
            $diff = new SongDifficulty();
            $diff->setSong($song);
            $diff->setDifficultyRank($difficultyRankRepository->findOneBy(["level" => $difficulty->_difficultyRank]));
            $diff->setDifficulty($difficulty->_difficulty);
            $diff->setNoteJumpMovementSpeed($difficulty->_noteJumpMovementSpeed);
            $diff->setNoteJumpStartBeatOffset($difficulty->_noteJumpStartBeatOffset);
            $em->persist($diff);
        }
        $em->flush();
        copy($folder . $json->_coverImageFilename, $kernel->getProjectDir() . "/public/covers/" . $song->getId() . $song->getCoverImageExtension());

        return $this->render('upload_song/index.html.twig', [
            'controller_name' => 'UploadSongController',
        ]);
    }
}
