<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Form\SongType;
use App\Repository\DifficultyRankRepository;
use App\Repository\SongRepository;
use App\Service\DiscordService;
use DateTime;
use Exception;
use Pkshetlie\PaginationBundle\Models\Pagination;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;

class UploadSongController extends AbstractController
{
    /**
     * @Route("/upload/song/edit/{id}", name="edit_song")
     */
    public function edit(Request $request, Song $song, TranslatorInterface $translator)
    {
        if($song->getUser() == $this->getUser() && !$this->isGranted('ROLE_ADMIN')){
            return new JsonResponse([
                'error' => true,
                'errorMessage' => $translator->trans("This Custom song is not your's"),
                'response' => ""
                ]);
        }
        $form = $this->createForm(SongType::class, $song, [
            'method' => "post",
            'action' => $this->generateUrl('edit_song', ['id' => $song->getId()])
        ]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $em = $this->getDoctrine()->getManager();
            $em->flush();
        }
        return new JsonResponse([
            'error' => false,
            'errorMessage' => "",
            'response' => $this->renderView('upload_song/partial/edit.html.twig', [
                'form' => $form->createView()
            ])
        ]);
    }

    /**
     * @Route("/upload/song/delete/{id}", name="delete_song")
     */
    public function delete(Song $song, KernelInterface $kernel)
    {
        if ($song->getUser() == $this->getUser()) {
            $em = $this->getDoctrine()->getManager();
            unlink($kernel->getProjectDir() . "/public/covers/" . $song->getId() . $song->getCoverImageExtension());
            unlink($kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip");
            $this->addFlash('success', "Song removed from catalog.");
            foreach ($song->getSongDifficulties() as $difficulty) {
                $em->remove($difficulty);
            }
            foreach ($song->getVotes() as $vote) {
                $em->remove($vote);
            }
            $em->remove($song);
            $em->flush();
            return $this->redirectToRoute("upload_song");
        } else {
            $this->addFlash('success', "You are not the file uploader..");
            return $this->redirectToRoute("upload_song");
        }
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
    public function index(Request $request, KernelInterface $kernel, DiscordService $discordService,
                          MailerInterface $mailer, SongRepository $songRepository, TranslatorInterface $translator,
                          DifficultyRankRepository $difficultyRankRepository, PaginationService $paginationService): Response
    {

        $form = $this->createFormBuilder()
            ->add("zipFile", FileType::class, [])
            ->add("description", TextareaType::class, [
                "required" => false,
                "attr" => ["placeholder" => $translator->trans("This one is not required, but if you put a youtube link in the description we can catch the first one as song video ! ;)")]
            ])
            ->add("converted", CheckboxType::class, ["required" => false])
            ->add("replaceExisting", CheckboxType::class, [
                "required" => false,
                'label' => "Replace existing song."
            ])
            ->getForm();
        $allowedFiles = [
            'preview.ogg',
            'info.dat'
        ];
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
                    if (!file_exists($file)) {
                        $file = $unzipFolder . "/Info.dat";
                        if (!file_exists($file)) {
                            $this->addFlash('danger', $translator->trans("The file seems to not be valid, at least info.dat is missing."));
                            $this->rrmdir($unzipFolder);
                            return $this->redirectToRoute("upload_song");
                        }
                    }
                    $json = json_decode(file_get_contents($file));
                    $allowedFiles[] = $json->_coverImageFilename;
                    $allowedFiles[] = $json->_songFilename;

                } catch (Exception $e) {
                    $this->addFlash('danger', $translator->trans("The file seems to not be valid, at least info.dat is missing."));
                    $this->rrmdir($unzipFolder);
                    return $this->redirectToRoute("upload_song");
                }
                /** @var Song $song */
                $song = $songRepository->findOneBy([
                    "name" => $json->_songName,
                    "authorName" => $json->_songAuthorName,
                    "levelAuthorName" => $json->_levelAuthorName,
                ]);
                $new = true;
                if ($song != null) {
                    $new = false;
                    if ($song->getUser() == $this->getUser() && $form->get('replaceExisting')->getData()) {

                    } else {
                        $this->rrmdir($unzipFolder);
                        $this->addFlash("danger", $translator->trans("The song \"%song%\" by \"%artist%\" is already in our catalog.", [
                            "%song%" => $song->getName(),
                            "%artist%" => $song->getAuthorName(),

                        ]));
                        return $this->redirectToRoute("upload_song");
                    }
                } else {
                    $song = new Song();
                    $song->setUser($this->getUser());
                }
                if ($form->get('description')->getData() != null) {
                    preg_match('~(?:https?://)?(?:www.)?(?:youtube.com|youtu.be)/(?:watch\?v=)?([^\s]+)~', $form->get('description')->getData(), $match);
                    if (count($match) > 0) {
                        $song->setYoutubeLink($match[0]);
                    } else {
                        $song->setYoutubeLink(null);
                    }
                    $song->setDescription($form->get('description')->getData());
                }

                $song->setVersion($json->_version);
                $song->setName($json->_songName);
                $song->setConverted((bool)$form->get('converted')->getData());
                $song->setLastDateUpload(new DateTime());
                $song->setSubName($json->_songSubName);
                $song->setAuthorName($json->_songAuthorName);
                $song->setLevelAuthorName($json->_levelAuthorName);
                $song->setBeatsPerMinute($json->_beatsPerMinute);
                $song->setShuffle($json->_shuffle);
                $song->setShufflePeriod($json->_shufflePeriod);
                $song->setPreviewStartTime($json->_previewStartTime);
                $song->setPreviewDuration($json->_previewDuration);
                try {
                    $song->setApproximativeDuration($json->_songApproximativeDuration);
                } catch (Exception $e) {
                    $this->addFlash("danger", $translator->trans("You don't add the _songApproximativeDuration in info.dat"));
                    return $this->redirectToRoute("upload_song");
                }
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


                copy($theZip, $finalFolder . $song->getId() . ".zip");
                copy($unzipFolder . "/" . $json->_coverImageFilename, $kernel->getProjectDir() . "/public/covers/" . $song->getId() . $song->getCoverImageExtension());
                $this->addFlash('success', $translator->trans("Song \"%song%\" by \"%artist%\" added !", [
                    "%song%" => $song->getName(),
                    "%artist%" => $song->getAuthorName()
                ]));
                $email = (new Email())
                    ->from('contact@ragnacustoms.com')
                    ->to('pierrick.pobelle@gmail.com')
                    ->subject('Nouvelle Map by ' . $this->getUser()->getUsername() . ', ' . $song->getName() . '!');
                if ($song->isModerated()) {
                    $discordService->sendNewSongMessage($song);
                    $email->html("Nouvelle map auto-modérée <a href='https://ragnacustoms.com" . $this->generateUrl('moderate_song', ['search' => $song->getName()]) . "'>verifier</a>");
                } else {
                    $email->html("Nouvelle map à modérée <a href='https://ragnacustoms.com" . $this->generateUrl('moderate_song', ['search' => $song->getName()]) . "'>verifier</a>");
                }
                $mailer->send($email);
            } catch (Exception $e) {
                $this->addFlash('danger', "Erreur lors de l'upload : " . $e->getMessage());
                return $this->redirectToRoute("upload_song");
            } finally {
                $this->rrmdir($unzipFolder);
            }

        }

        $qb = $songRepository->createQueryBuilder('song')
            ->where('song.user = :user')
            ->setParameter('user', $this->getUser())
            ->orderBy('song.lastDateUpload', 'DESC');

        $pagination = $paginationService->setDefaults(30)->process($qb, $request);
        if ($pagination->isPartial()) {
            return $this->render('upload_song/partial/uploaded_song_row.html.twig', [
                'songs' => $pagination
            ]);
        }
        return $this->render('upload_song/index.html.twig', [
            'form' => $form->createView(),
            'songs' => $pagination
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
