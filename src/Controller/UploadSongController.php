<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Repository\DifficultyRankRepository;
use App\Repository\SongRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;
use ZipArchive;

class UploadSongController extends AbstractController
{
    /**
     * @Route("/upload/song", name="upload_song")
     */
    public function index(Request $request, KernelInterface $kernel, SongRepository $songRepository, DifficultyRankRepository $difficultyRankRepository): Response
    {

        $form = $this->createFormBuilder()
            ->add("zipFile", FileType::class, [])
            ->add("send", SubmitType::class, [])
            ->getForm();

        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()) {
            $folder = $kernel->getProjectDir() . "/public/tmp-song/";
            $unzipFolder = $folder . uniqid();
            try {
                /** @var UploadedFile $file */
                $file = $form->get('zipFile')->getData();
                $file->move($unzipFolder, $file->getClientOriginalName());
                $zip = new ZipArchive();
                $theZip = $unzipFolder . "/" . $file->getClientOriginalName();
                if ($zip->open($theZip) === TRUE) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        $elt = $zip->getFromIndex($i);
                        $exp = explode("/", $filename);
                        if (end($exp) != "") {
                            $fileinfo = pathinfo($filename);
                            $result = file_put_contents($unzipFolder . "/" . $fileinfo['basename'], $elt);
                        }
                    }
                    $zip->close();

                } else {
                }
                $json = json_decode(file_get_contents($unzipFolder . "/info.dat"));
                $song = $songRepository->findBy([
                    "name" => $json->_songName,
                    "authorName" => $json->_songAuthorName,
                    "levelAuthorName" => $json->_levelAuthorName,
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
                copy($theZip, $folder . $song->getId() . ".zip");
                copy($unzipFolder . "/" . $json->_coverImageFilename, $kernel->getProjectDir() . "/public/covers/" . $song->getId() . $song->getCoverImageExtension());
            } catch (Exception $e) {
                $this->addFlash('danger', "Erreur lors de l'upload : " . $e->getMessage());
            } finally {
                $this->rrmdir($unzipFolder);
                unlink($theZip);
            }

        }
        return $this->render('upload_song/index.html.twig', [
            'controller_name' => 'UploadSongController',
            'form' => $form->createView()
        ]);
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
