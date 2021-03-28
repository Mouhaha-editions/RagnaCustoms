<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Repository\DifficultyRankRepository;
use App\Repository\SongRepository;
use Exception;
use Pkshetlie\PaginationBundle\Models\Pagination;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
     * @Route("/upload/song/delete/{id}", name="delete_song")
     */
    public function delete(Song $song, KernelInterface $kernel)
    {
        if($song->getUser() == $this->getUser()){
            $em = $this->getDoctrine()->getManager();
            unlink($kernel->getProjectDir() . "/public/covers/" . $song->getId() . $song->getCoverImageExtension());
            unlink($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() .".zip");
            $this->addFlash('success', "Song removed from catalog.");
            foreach($song->getSongDifficulties() AS $difficulty){
                $em->remove($difficulty);
            }
            foreach($song->getVotes() AS $vote){
                $em->remove($vote);
            }
            $em->remove($song);
            $em->flush();
            return $this->redirectToRoute("upload_song");
        }else{
            $this->addFlash('success', "You are not the file uploader..");
            return $this->redirectToRoute("upload_song");
        }
}
    /**
     * @Route("/upload/song/list", name="my_songs")
     */
    public function list(Request $request, SongRepository $songRepository, PaginationService $paginationService): Response
    {
        $qb = $songRepository->createQueryBuilder('song')
            ->where('song.user = :user')
            ->setParameter('user', $this->getUser());

        $pagination = $paginationService->setDefaults(30)->process($qb, $request);

        return $this->render('upload_song/manage.html.twig', [
            "songs" => $pagination
        ]);
    }

    /**
     * @Route("/upload/song", name="upload_song")
     * @param Request $request
     * @param KernelInterface $kernel
     * @param SongRepository $songRepository
     * @param DifficultyRankRepository $difficultyRankRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    public function index(Request $request, KernelInterface $kernel, SongRepository $songRepository,
                          DifficultyRankRepository $difficultyRankRepository, PaginationService $paginationService): Response
    {

        $form = $this->createFormBuilder()
            ->add("zipFile", FileType::class, [])
            ->add("replaceExisting", CheckboxType::class, ["required"=>false,'label'=>"Replace existing song."])
            ->getForm();

        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()) {
            $finalFolder = $kernel->getProjectDir() . "/public/songs-files/";
            $folder = $kernel->getProjectDir() . "/public/tmp-song/";
            $unzipFolder = $folder . uniqid();
            $file = $form->get('zipFile')->getData();
            $file->move($unzipFolder, $file->getClientOriginalName());
            $zip = new ZipArchive();
            $theZip = $unzipFolder . "/" . $file->getClientOriginalName();
            try {
                /** @var UploadedFile $file */

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

                }
                try {
                    $file = $unzipFolder . "/info.dat";
                    if(!file_exists($file)){
                        $file = $unzipFolder . "/Info.dat";
                        if(!file_exists($file)){
                            $this->addFlash('danger', "The file seems to not be valid, at least info.dat is missing.");
                            $this->rrmdir($unzipFolder);
                            return $this->redirectToRoute("upload_song");
                        }
                    }
                    $json = json_decode(file_get_contents($file));
                }catch(Exception $e){
                    $this->addFlash('danger', "The file seems to not be valid, at least info.dat is missing.");
                    $this->rrmdir($unzipFolder);
                    return $this->redirectToRoute("upload_song");
                }
                /** @var Song $song */
                $song = $songRepository->findOneBy([
                    "name" => $json->_songName,
                    "authorName" => $json->_songAuthorName,
                    "levelAuthorName" => $json->_levelAuthorName,
                ]);
                if ($song != null) {
                    if($song->getUser() == $this->getUser() && $form->get('replaceExisting')->getData()){

                    }else {
                        $this->rrmdir($unzipFolder);
                        $this->addFlash("danger", "The song \"".$song->getName()."\" by \"".$song->getAuthorName()."\" is already in our catalog.");
                        return $this->redirectToRoute("upload_song");
                    }
                }else{
                    $song = new Song();
                    $song->setUser($this->getUser());
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
                $em->persist($song);
                foreach($song->getSongDifficulties() AS $difficulty){
                    $em->remove($difficulty);
                }
                foreach($song->getVotes() AS $vote){
                    $em->remove($vote);
                }
                $song->setVoteDown(0);
                $song->setVoteUp(0);
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
                copy($theZip, $finalFolder . $song->getId() . ".zip");
                copy($unzipFolder . "/" . $json->_coverImageFilename, $kernel->getProjectDir() . "/public/covers/" . $song->getId() . $song->getCoverImageExtension());
                $this->addFlash('success', "Song \"".$song->getName()."\" by \"".$song->getAuthorName()."\" added !");
            } catch (Exception $e) {
                $this->addFlash('danger', "Erreur lors de l'upload : " . $e->getMessage());
                return $this->redirectToRoute("upload_song");
            } finally {
                $this->rrmdir($unzipFolder);
            }

        }

        $qb = $songRepository->createQueryBuilder('song')
            ->where('song.user = :user')
            ->setParameter('user', $this->getUser());

        $pagination = $paginationService->setDefaults(30)->process($qb, $request);

        return $this->render('upload_song/index.html.twig', [
            'controller_name' => 'UploadSongController',
            'form' => $form->createView(),
            'songs'=>$pagination
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
