<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\SongRequest;
use App\Form\SongType;
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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
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

        $form = $this->createForm(SongType::class, $song, [
            'method' => "post",
            'action' => $song->getId() != null ? $this->generateUrl('edit_song', ['id' => $song->getId()]
            ) : $this->generateUrl('new_song'),
        ]);

        if ($this->isGranted('ROLE_PREMIUM_LVL3')) {
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
                ->add('publishingType', ChoiceType::class, [
                    'choices' => [
                        'Public' => 1,
                        'Private link' => 2,
                        'WIP' => 0,
                        'Unpublished' => 3,
                    ],
                    'mapped' => false
                ])
                ->add("zipFile", FileType::class, [
                    "mapped" => false,
                    "required" => $song->getId() == null,
                    "help" => "Upload a .zip file (max 30Mo) containing all the files for the map.",
                    "constraints" => [
                        new File([
                            'maxSize' => '30m',
                            'maxSizeMessage' => 'You can upload up to 30Mo with a premium account Tier 3',
                        ], '30m'),
                    ],
                ]);
        } elseif ($this->isGranted('ROLE_PREMIUM_LVL2')) {
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
                            'maxSize' => '15m',
                            'maxSizeMessage' => 'You can upload up to 15Mo with a premium account Tier 2',
                        ], '15m'),
                    ],
                ])
                ->add('publishingType', ChoiceType::class, [
                    'choices' => [
                        'Public' => 1,
                        'Private link' => 2,
                        'WIP' => 0,
                        'Unpublished' => 3,
                    ],
                    'mapped' => false
                ]);
        } else {
            $form->add('publishingType', ChoiceType::class, [
                'choices' => [
                    'Public' => 1,
                    'WIP' => 0,
                    'Unpublished' => 3,
                ],
                'mapped' => false,
                'help_html'=> true,
                "help" => "<i class='fa fa-gavel text-warning'></i> Premium member Tier 2 can publish as private link",

            ]);

            if ($this->isGranted('ROLE_PREMIUM_LVL1')) {
                $form->add("zipFile", FileType::class, [
                    "mapped" => false,
                    "required" => $song->getId() == null,
                    "help" => "Upload a .zip file (max 10Mo) containing all the files for the map, upgrade your Premium member Tier 2 to upload more.",
                    "constraints" => [
                        new File([
                            'maxSize' => '10m',
                            'maxSizeMessage' => 'You can upload up to 10Mo with a premium account Tier 1',
                        ], '10m'),
                    ],
                ]);
            } else {
                $form->add("zipFile", FileType::class, [
                    "mapped" => false,
                    "required" => $song->getId() == null,
                    "help" => "Upload a .zip file (max 8Mo) containing all the files for the map.",
                    "constraints" => [
                        new File([
                            'maxSize' => '8m',
                            'maxSizeMessage' => 'You can upload up to 8Mo without a premium account',
                        ], '8m'),
                    ],
                ]);
            }
        }

        $isWip = $song->getWip();
        $form->get('publishingType')->setData($song->isWip() ? 0 : ($song->isPrivate() ? 2 : 1));
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            try {
                if (!$form->isValid()) {
                    throw new Exception(
                        'An error occurs, please check the form for more informations.'
                    );
                }

                $song->addMapper($this->getUser());

                if (!count($song->getBestPlatform())) {
                    throw new Exception(
                        'Select on which version your map is meant to be played (VR and/or Vikings on Tour)'
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

                switch ((int)$form->get('publishingType')->getData()) {
                    case 0 :
                    default:
                        $song->setWip(true);
                        $song->setActive(true);
                        $song->setPrivate(false);
                        $song->setPrivateLink(null);
                        break;
                    case 1:
                        $song->setActive(true);
                        $song->setPrivate(false);
                        $song->setPrivateLink(null);
                        $song->setWip(false);
                        break;
                    case 2:
                        if ($this->isGranted('ROLE_PREMIUM_LVL2')) {
                            $song->setWip(false);
                            $song->setPrivate(true);
                            $song->setActive(false);

                            if (!$song->getPrivateLink()) {
                                $song->setPrivateLink($songService->generateLink());
                            }
                        }
                        break;
                    case 3 :
                        $song->setWip(false);
                        $song->setActive(false);
                        $song->setPrivate(false);
                        $song->setPrivateLink(null);
                        break;
                }


                $file = $form->get('zipFile')->getData();

                if ($file == null) {
                    if (empty($song->getBestPlatform())) {
                        throw new Exception('Please choose at least one platform');
                    }

                    $message = $translator->trans(
                        "Song \"%song%\" by \"%artist%\" successfully uploaded!".(
                        $song->isPrivate() ?
                            "<br/>Your private link is : <code>%url%</code> <br/> 
                              <small>You can copy this one by clicking on the lock in your song list</small>" : ''
                        ),
                        [
                            "%song%" => $song->getName(),
                            "%artist%" => $song->getAuthorName(),
                            '%url%' => !$song->isPrivate() ? '' : $this->generateUrl('secure_song', ['privateLink' => $song->getPrivateLink()], UrlGenerator::ABSOLUTE_URL),
                        ]
                    );
                    $this->addFlash('success', $message);

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

                if ($song->getUpdatedAt() <= (new DateTime('now'))->modify('-1 days')) {
                    $song->setNotificationDone(false);
                }

                if ($songService->processFile($form, $song, $isWip)) {
                    /** @var ?SongRequest $song_request */
                    $doctrine->getManager()->flush();
                    if($song->isPrivate()) {
                        $this->addFlash(
                            'success',
                            $translator->trans(
                                "Song \"%song%\" by \"%artist%\" successfully uploaded!<br/>".
                                "Your private link is : %url% <br/> <small>You can copy this one by clicking on the lock in your song list</small>",
                                [
                                    "%song%" => $song->getName(),
                                    "%artist%" => $song->getAuthorName(),
                                    '%url%' => $this->generateUrl('secure_song', ['key' => $song->getPrivateLink()]),
                                ]
                            )
                        );
                    }else{
                        $this->addFlash(
                            'success',
                            $translator->trans("Song \"%song%\" by \"%artist%\" successfully uploaded!", [
                                "%song%" => $song->getName(),
                                "%artist%" => $song->getAuthorName(),
                            ])
                        );
                    }

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
    public function delete(
        Song $song,
        EntityManagerInterface $em,
        DiscordService $discordService,
        SongService $songService
    ) {
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
        SongService $songService,
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
