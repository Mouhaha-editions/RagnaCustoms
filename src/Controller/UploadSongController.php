<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\SongRequest;
use App\Form\SongType;
use App\Repository\SongRepository;
use App\Service\ScoreService;
use App\Service\SongService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Intervention\Image\ImageManagerStatic as Image;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadSongController extends AbstractController
{

    /**
     * @Route("/upload/song/new", name="new_song")
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param SongService $songService
     * @param ScoreService $scoreService
     * @return JsonResponse
     */
    public function new(Request $request, TranslatorInterface $translator,ManagerRegistry $doctrine, SongService $songService, ScoreService $scoreService)
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
        return $this->edit($request, $song, $doctrine, $translator, $songService, $scoreService);
    }


    /**
     * @Route("/upload/song/edit/{id}", name="edit_song")
     * @param Request $request
     * @param Song $song
     * @param TranslatorInterface $translator
     * @param SongService $songService
     * @param ScoreService $scoreService
     * @return JsonResponse
     */
    public function edit(Request $request,Song $song,ManagerRegistry $doctrine,  TranslatorInterface $translator, SongService $songService, ScoreService $scoreService)
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

        if ($form->isSubmitted() && $form->isSubmitted()) {
            try {
                $file = $form->get('zipFile')->getData();
                if ($file == null) {
                    $this->addFlash('success', str_replace([
                        "%song%",
                        "%artist%"
                    ], [
                        $song->getName(),
                        $song->getAuthorName()
                    ], $translator->trans("Song \"%song%\" by \"%artist%\" successfully uploaded!")));
                    $em = $doctrine->getManager();
                    $em->persist($song);
                    $em->flush();
                    return new JsonResponse([
                        'error' => false,
                        'goto' => $this->generateUrl('song_detail', ['slug' => $song->getSlug()]),
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
                    if ($song_request != null) {
                        $song_request->setState(SongRequest::STATE_ENDED);
                        if ($song_request->getWantToBeNotified()) {
                            $songService->emailRequestDone($song_request, $song);
                        }
                        $doctrine->getManager()->flush();
                    }

                    $this->addFlash('success', str_replace([
                        "%song%",
                        "%artist%"
                    ], [
                        $song->getName(),
                        $song->getAuthorName()
                    ], $translator->trans("Song \"%song%\" by \"%artist%\" successfully uploaded!")));
                    return new JsonResponse([
                        'error' => false,
                        'goto' => $this->generateUrl('song_detail', ['slug' => $song->getSlug()]),
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
                    'errorMessage' => $e->getMessage(),
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
            "goto" => $this->redirectToRoute("upload_song"),
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
            $song->setSlug($song->getSlug() . '-deleted');
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

        $qb = $songRepository->createQueryBuilder('s')->select('s')->addSelect('s.voteUp - s.voteDown AS HIDDEN rating')->where('s.user = :user')->andWhere("s.isDeleted != true")->setParameter('user', $this->getUser())->orderBy('s.name', 'DESC');

        if ($request->get('search', null)) {
            $exp = explode(':', $request->get('search'));
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
        if ($request->get('order_by') && in_array($request->get('order_by'), [
                's.lastDateUpload',
                'rating',
                's.downloads',
                's.name'
            ], true)) {
            $qb->orderBy($request->get('order_by'), $request->get('order_sort', 'asc'));
        }else{
            $qb->orderBy("s.lastDateUpload", "desc");
        }
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

    function stripUtf16Le($string)
    {
        return preg_replace('/^\xff\xfe/', '', $string);
    }

    function stripUtf16Be($string)
    {
        return preg_replace('/^\xfe\xff/', '', $string);
    }

    function stripUtf8Bom($string)
    {
        return preg_replace('/^\xef\xbb\xbf/', '', $string);
    }

    public function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object); else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }

}
