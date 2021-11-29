<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\SongCategory;
use App\Entity\SongDifficulty;
use App\Entity\SongRequest;
use App\Form\SongType;
use App\Repository\DifficultyRankRepository;
use App\Repository\SongRepository;
use App\Repository\SongRequestRepository;
use App\Service\DiscordService;
use App\Service\SongService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Pkshetlie\PaginationBundle\Models\Pagination;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
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
     * @Route("/upload/song/new", name="new_song")
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param SongService $songService
     * @return JsonResponse
     */
    public function new(Request $request, TranslatorInterface $translator, SongService $songService)
    {
        if (!$this->isGranted("ROLE_USER")) {
            return new JsonResponse([
                'error' => true,
                'errorMessage' => $translator->trans("You need to be connected"),
                'response' => ""
            ]);
        }
        $song = new Song();
        $song->setUser($this->getUser());
        return $this->edit($request, $song, $translator, $songService);
    }


    /**
     * @Route("/upload/song/edit/{id}", name="edit_song")
     * @param Request $request
     * @param Song $song
     * @param TranslatorInterface $translator
     * @param SongService $songService
     * @return JsonResponse
     */
    public function edit(Request $request, Song $song, TranslatorInterface $translator, SongService $songService)
    {
        if ($song->getUser() != $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse([
                'error' => true,
                'errorMessage' => $translator->trans("This Custom song is not your's"),
                'response' => ""
            ]);
        }
        $form = $this->createForm(SongType::class, $song, [
            'method' => "post",
            'action' => $song->getId() != null ? $this->generateUrl('edit_song', ['id' => $song->getId()]) : $this->generateUrl('new_song')
        ]);

        $isWip = $song->getWip();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $file = $form->get('zipFile')->getData();
                if($file == null){

                    $this->addFlash('success', str_replace(["%song%","%artist%"],[$song->getName(),$song->getAuthorName()],$translator->trans("Song \"%song%\" by \"%artist%\" successfully uploaded!")));
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($song);
                    $em->flush();
                    return new JsonResponse([
                        'error' => false,
                        'reload' => true,
                        'errorMessage' => null,
                        'response' => $this->renderView('upload_song/partial/edit.html.twig', [
                            'form' => $form->createView(),
                            'song' => $song,
                            "error" => null
                        ])
                    ]);
                }

                if ($songService->processFile($form, $song, $isWip)) {
                    /** @var ?SongRequest $song_request */
                    $song_request = $form->get('song_request')->getData();
                    if($song_request != null){
                        $song_request->setState(SongRequest::STATE_ENDED);
                        $this->getDoctrine()->getManager()->flush();
                    }
                    $this->addFlash('success', str_replace(["%song%","%artist%"],[$song->getName(),$song->getAuthorName()],$translator->trans("Song \"%song%\" by \"%artist%\" successfully uploaded!")));
                    return new JsonResponse([
                        'error' => false,
                        'reload' => true,
                        'errorMessage' => null,
                        'response' => $this->renderView('upload_song/partial/edit.html.twig', [
                            'form' => $form->createView(),
                            'song' => $song,
                            "error" => null
                        ])
                    ]);
                }
            } catch (Exception $e) {
                return new JsonResponse([
                    'error' => true,
                    'errorMessage' => null,
                    'response' => $this->renderView('upload_song/partial/edit.html.twig', [
                        'form' => $form->createView(),
                        'song' => $song,
                        "error" => $e->getMessage()
                    ])
                ]);
            }
        }
        return new JsonResponse([
            'error' => false,
            'errorMessage' => "",
            'response' => $this->renderView('upload_song/partial/edit.html.twig', [
                'form' => $form->createView(),
                'song' => $song,
                "error" => null
            ])
        ]);
    }

    /**
     * @Route("/upload/song/delete/{id}", name="delete_song")
     */
    public function delete(Song $song, KernelInterface $kernel, EntityManagerInterface $em)
    {

        if ($song->getUser() == $this->getUser()) {
            $song->setIsDeleted(true);
            $this->addFlash('success', "Song removed from catalog.");

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
     * @param SongRepository $songRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    public function index(Request $request, SongRepository $songRepository, PaginationService $paginationService): Response
    {

        $qb = $songRepository->createQueryBuilder('song')
            ->where('song.user = :user')
            ->andWhere("song.isDeleted != true")
            ->setParameter('user', $this->getUser())
            ->orderBy('song.lastDateUpload', 'DESC');

        $pagination = $paginationService->setDefaults(30)->process($qb, $request);
        if ($pagination->isPartial()) {
            return $this->render('upload_song/partial/uploaded_song_row.html.twig', [
                'songs' => $pagination
            ]);
        }
        return $this->render('upload_song/index.html.twig', [
            'songs' => $pagination
        ]);
    }

    function remove_utf8_bom($text)
    {
        return $this->stripUtf16Le($this->stripUtf16Be($this->stripUtf8Bom($text)));//mb_convert_encoding($text, 'UTF-8', 'UCS-2LE');
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

    function stripUtf8Bom($string)
    {
        return preg_replace('/^\xef\xbb\xbf/', '', $string);
    }

    function stripUtf16Le($string)
    {
        return preg_replace('/^\xff\xfe/', '', $string);
    }

    function stripUtf16Be($string)
    {
        return preg_replace('/^\xfe\xff/', '', $string);
    }

}
