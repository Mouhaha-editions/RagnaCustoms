<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\SongRequest;
use App\Entity\Utilisateur;
use App\Form\SongType;
use App\Form\UtilisateurAutocompleteField;
use App\Repository\SongRepository;
use App\Service\DiscordService;
use App\Service\ScoreService;
use App\Service\SongService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadSongController extends AbstractController
{
    #[Route(path: '/upload/song/new', name: 'new_song')]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $doctrine,
        SongService $songService,
        ScoreService $scoreService
    ) {
        if (!$this->isGranted("ROLE_USER")) {
            return new JsonResponse([
                'error' => true,
                'errorMessage' => $translator->trans("You need to be connected"),
                'response' => "",
            ]);
        }

        $song = new Song();
        $song->setProgrammationDate(new DateTime());
        $song->addMapper($this->getUser());

        return $this->edit($request, $song, $doctrine, $translator, $songService, $scoreService);
    }

    #[Route(path: '/upload/song/edit/{id}', name: 'edit_song')]
    public function edit(
        Request $request,
        Song $song,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        SongService $songService,
        ScoreService $scoreService
    ) {
        if (!$song->getMappers()->contains($this->getUser())) {
            return new JsonResponse([
                'error' => true,
                'errorMessage' => $translator->trans("This Custom song is not your's"),
                'response' => "",
            ]);
        }
//        $song->removeMapper($this->getUser());
        $form = $this->createForm(SongType::class, $song, [
            'method' => "post",
            'action' => $song->getId() != null ? $this->generateUrl('edit_song', ['id' => $song->getId()]
            ) : $this->generateUrl('new_song'),
        ]);


        if ($this->isGranted('ROLE_PREMIUM_LVL2')) {
            $form
                ->add('programmationDate', DateTimeType::class, [
                    'label' => '<i data-toggle="tooltip" title="premium feature" class="fas fa-gavel text-warning" ></i> Publishing date',
                    'widget' => 'single_text',
                    'required' => true,
                    'input' => "datetime",
                    "empty_data" => '',
                    'label_html' => true,
                    'help' => "Sorry for now it's based on UTC+1 (french time) ",
                ])
                ->add("zipFile", FileType::class, [
                    "mapped" => false,
                    "required" => $song->getId() == null,
                    "help" => "Upload a .zip file (max 15Mo) containing all the files for the map.",
                    "constraints" => [
                        new File([
                            'maxSize' => '15M',
                            'maxSizeMessage' => 'You can upload up to 50Mo with a premium account Tier 2',
                        ]),
                    ],
                ]);
        } else {
            if ($this->isGranted('ROLE_PREMIUM_LVL1')) {
                $form->add("zipFile", FileType::class, [
                    "mapped" => false,
                    "required" => $song->getId() == null,
                    "help" => "Upload a .zip file (max 10Mo) containing all the files for the map, upgrade your Premium member Tier 2 to upload more.",
                    "constraints" => [
                        new File([
                            'maxSize' => '10M',
                            'maxSizeMessage' => 'You can upload up to 10Mo with a premium account Tier 1',
                        ]),
                    ],
                ]);
            } else {
                $form->add("zipFile", FileType::class, [
                    "mapped" => false,
                    "required" => $song->getId() == null,
                    "help" => "Upload a .zip file (max 8Mo) containing all the files for the map.",
                    "constraints" => [
                        new File([
                            'maxSize' => '8M',
                            'maxSizeMessage' => 'You can upload up to 8Mo without a premium account',
                        ]),
                    ],
                ]);
            }
        }

        $isWip = $song->getWip();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isSubmitted()) {
            $song->addMapper($this->getUser());
            try {
                if (!count($song->getBestPlatform())) {
                    throw new Exception(
                        'Select on which version your map is planed to be played (VR and/or Viking On Tour)'
                    );
                }

                if (!empty($song->getYoutubeLink())) {
                    if (!preg_match(
                        "/^(https?\:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/(.*)$/",
                        $song->getYoutubeLink()
                    )) {
                        throw new Exception('This is not a youtube link, please edit it');
                    }
                }

                $file = $form->get('zipFile')->getData();

                if ($file == null) {
                    if (empty($song->getBestPlatform())) {
                        throw new Exception('Please choose at least one platform');
                    }

                    $this->addFlash(
                        'success',
                        str_replace([
                            "%song%",
                            "%artist%",
                        ], [
                            $song->getName(),
                            $song->getAuthorName(),
                        ], $translator->trans("Song \"%song%\" by \"%artist%\" successfully uploaded!"))
                    );
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
                            "error" => null,
                        ]),
                    ]);
                }

                if ($songService->processFile($form, $song, $isWip)) {
                    /** @var ?SongRequest $song_request */
                    $doctrine->getManager()->flush();
                    $this->addFlash(
                        'success',
                        str_replace([
                            "%song%",
                            "%artist%",
                        ], [
                            $song->getName(),
                            $song->getAuthorName(),
                        ], $translator->trans("Song \"%song%\" by \"%artist%\" successfully uploaded!"))
                    );

                    return new JsonResponse([
                        'error' => false,
                        'goto' => $this->generateUrl('song_detail', ['slug' => $song->getSlug()]),
                        'reload' => true,
                        'errorMessage' => null,
                        'response' => $this->renderView('upload_song/partial/edit.html.twig', [
                            'form' => $form->createView(),
                            'song' => $song,
                            "error" => null,
                        ]),
                    ]);
                }
            } catch (Exception $e) {
                return new JsonResponse([
                    'error' => true,
                    'errorMessage' => $e->getMessage(),
                    'response' => $this->renderView('upload_song/partial/edit.html.twig', [
                        'form' => $form->createView(),
                        'song' => $song,
                        "error" => $e->getMessage(),
                    ]),
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
                "error" => null,
            ]),
        ]);
    }

    #[Route(path: '/upload/song/delete/{id}', name: 'delete_song')]
    public function delete(Song $song, EntityManagerInterface $em, DiscordService $discordService, SongService $songService)
    {
        if ($song->getMappers()->contains($this->getUser()) && !$song->isRanked()) {
            $songService->cleanUp($song);
            $discordService->deletedSong($song);

            return new JsonResponse(['success' => true]);
        } else {
            return new JsonResponse(['success' => false]);
        }
    }

    #[Route(path: '/upload/song/toggle/{id}', name: 'upload_song_toggle')]
    public function toggleSong(Request $request, Song $song, SongRepository $songRepository)
    {
        if (!$song->getMappers()->contains($this->getUser())) {
            return new JsonResponse([
                'success' => false,
                'message' => "This is not YOUR song",
            ]);
        }

        if ($song->getCategoryTags()->count() == 0) {
            return new JsonResponse([
                'success' => false,
                'message' => "You need at least 1 category for this song",
            ]);
        }

        $song->setActive(!$song->getActive());
        $songRepository->add($song);

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/upload/song', name: 'upload_song')]
    public function index(
        Request $request,
        SongRepository $songRepository,
        PaginationService $paginationService
    ): Response {
        $qb = $songRepository->createQueryBuilder('s')
            ->select('s')
            ->leftJoin('s.categoryTags', 't')
            ->leftJoin('s.mappers', 'm')
            ->addSelect('s.voteUp - s.voteDown AS HIDDEN rating')
            ->where('m.id = :user')
            ->andWhere("s.isDeleted != true")
            ->setParameter('user', $this->getUser())
            ->groupBy('s.id')
            ->orderBy('s.name', 'DESC');

        if ($request->get('search')) {
            $exp = explode(':', $request->get('search'));
            switch ($exp[0]) {
                case 'mapper':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.levelAuthorName LIKE :search_string)')->setParameter(
                            'search_string',
                            '%'.$exp[1].'%'
                        );
                    }
                    break;

                case 'artist':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.authorName LIKE :search_string)')->setParameter(
                            'search_string',
                            '%'.$exp[1].'%'
                        );
                    }
                    break;
                case 'title':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.name LIKE :search_string)')->setParameter('search_string', '%'.$exp[1].'%');
                    }
                    break;
                case 'desc':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.description LIKE :search_string)')->setParameter(
                            'search_string',
                            '%'.$exp[1].'%'
                        );
                    }
                    break;
                case 'genre':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(t.label LIKE :search_string)')->setParameter('search_string', '%'.$exp[1].'%');
                    }
                    break;
                default:
                    $qb->andWhere(
                        $qb->expr()->orX(
                            's.name LIKE :search_string',
                            's.authorName LIKE :search_string',
                            's.description LIKE :search_string',
                            's.levelAuthorName LIKE :search_string',
                            't.label LIKE :search_string',
                        )
                    )->setParameter('search_string', '%'.$request->get('search').'%');
            }
        }

        if ($request->get('order_by') && in_array($request->get('order_by'), [
                's.programmationDate',
                'rating',
                's.downloads',
                's.name',
            ], true)) {

            if ($request->get('order_by') === 's.programmationDate') {
                $qb->orderBy(
                    "IF(s.programmationDate IS NULL,s.createdAt,s.programmationDate)",
                    $request->get('order_sort', 'asc')
                );
            } else {
                $qb->orderBy($request->get('order_by'), $request->get('order_sort', 'asc'));
            }
        } else {
            $qb->orderBy("IF(s.programmationDate IS NULL,s.createdAt,s.programmationDate)", "desc");
        }

        $pagination = $paginationService->setDefaults(30)->process($qb, $request);

        if ($pagination->isPartial()) {
            return $this->render('upload_song/partial/uploaded_song_row.html.twig', [
                'songs' => $pagination,
            ]);
        }

        return $this->render('upload_song/index.html.twig', [
            'songs' => $pagination,
        ]);
    }

    function remove_utf8_bom($text)
    {
        return $this->stripUtf16Le(
            $this->stripUtf16Be($this->stripUtf8Bom($text))
        );//mb_convert_encoding($text, 'UTF-8', 'UCS-2LE');
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
                    if (is_dir($dir.DIRECTORY_SEPARATOR.$object) && !is_link($dir."/".$object)) {
                        $this->rrmdir($dir.DIRECTORY_SEPARATOR.$object);
                    } else {
                        unlink($dir.DIRECTORY_SEPARATOR.$object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    #[Route(path: '/upload/song/new-multi', name: 'new_song_multi')]
    public function indexV2(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $doctrine,
        SongService $songService,
        ScoreService $scoreService
    ) {
        return $this->render('upload_song/index_multi.html.twig');
    }

    #[Route(path: '/upload/bundle/song/add', name: 'bundle_song')]
    public function bundleUpload(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $doctrine,
        SongService $songService,
        ScoreService $scoreService
    ) {
        try {
            $song = new Song();
            $song->addMapper($this->getUser());
            $song->setActive(true);
            $songService->processFileWithoutForm($request, $song);
        } catch (Exception $e) {
            return new Response($e->getMessage(), 500);
        }

        return new JsonResponse([
            "success" => true,
            'cover' => $song->getCover(),
        ]);
    }
}
