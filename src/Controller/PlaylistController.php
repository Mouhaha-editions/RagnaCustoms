<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\Song;
use App\Form\PlaylistType;
use App\Repository\PlaylistRepository;
use App\Repository\SongRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlaylistController extends AbstractController
{
    #[Route(path: '/playlists', name: 'playlist')]
    public function index(): Response
    {
        /** @var Playlist[] $playlists */
        $playlists = $this->getUser()->getPlaylists();

        return $this->render('playlist/index.html.twig', [
            'playlists' => $playlists,
        ]);
    }

    /**
     * @param Request $request
     * @param Playlist $playlist
     * @param PaginationService $paginationService
     * @param SongRepository $songRepository
     * @return Response
     */
    #[Route(path: '/playlist/show/{id}', name: 'playlist_show')]
    public function show(Request $request, Playlist $playlist, PaginationService $paginationService,
                         SongRepository $songRepository): Response
    {
        if (!$playlist->getIsPublic() && $playlist->getUser() !== $this->getUser()) {
            $this->addFlash("warning", "This playlist is not public");
            return $this->redirectToRoute('home');
        }
        $qb = $songRepository->createQueryBuilder('song')
            ->leftJoin('song.playlists', 'playlist')
            ->where('playlist = :playlist')
            ->setParameter("playlist", $playlist)
            ->addOrderBy('song.name');
        if ($request->get('oneclick_dl')) {
            $ids = $qb->select('song.id')->getQuery()->getArrayResult();
            return $this->redirect("ragnac://install/" . implode('-', array_map(function ($id) {
                    return array_pop($id);
                }, $ids)));
        }
        $songs = $paginationService->setDefaults(72)->process($qb, $request);
        return $this->render('playlist/show.html.twig', [
            'playlist' => $playlist,
            'songs' => $songs,
            "user" => $playlist->getUser()

        ]);
    }


    #[Route(path: '/playlist/remove', name: 'playlist_remove')]
    public function remove(
        Request $request,
        ManagerRegistry $doctrine,
        PlaylistRepository $playlistRepository,
        SongRepository $songRepository
    ) {
        $playlist = $playlistRepository->find($request->get('playlist_id'));

        if ($this->getUser()->getId() == $playlist->getUser()->getId() || $this->isGranted('ROLE_ADMIN')) {
            $song = $songRepository->find($request->get('id'));
            $playlist->removeSong($song);
            $doctrine->getManager()->flush();
            $this->addFlash('danger', 'Song removed.');
        }else{
            $this->addFlash('danger', "You are not the owner of this playlist.");
        }

        return $this->redirectToRoute('playlist_show', ['id' => $playlist->getId()]);
    }

    /**
     * @param Request $request
     * @param Playlist $playlist
     * @param SongRepository $songRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route(path: '/playlist/edit/{id}', name: 'playlist_edit')]
    public function edit(Request $request,ManagerRegistry $doctrine, Playlist $playlist, SongRepository $songRepository, PaginationService $paginationService)
    {
        if (!$this->isGranted("ROLE_USER") || $this->getUser()->getId() !== $playlist->getUser()->getId()) {
            $this->addFlash("danger", "You are not the owner of this playlist.");
            return $this->redirectToRoute("playlist");
        }
        $form = $this->createForm(PlaylistType::class, $playlist);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $doctrine->getManager()->flush();
            $this->addFlash('success',"Playlist edited!");
        }

        $qb = $songRepository->createQueryBuilder("song")
            ->leftJoin("song.playlists", 'playlist')
            ->where('playlist = :playlist')
            ->setParameter("playlist", $playlist)
            ->addOrderBy('song.name');
        if ($request->get('oneclick_dl')) {
            $ids = $qb->select('song.id')->getQuery()->getArrayResult();
            return $this->redirect("ragnac://install/" . implode('-', array_map(function ($id) {
                    return array_pop($id);
                }, $ids)));
        }
        $songs = $paginationService->setDefaults(72)->process($qb, $request);
        return $this->render('playlist/edit.html.twig', [
            'form' => $form->createView(),
            'playlist' => $playlist,
            'songs' => $songs
        ]);
    }

    /**
     * @param Request $request
     * @param Playlist $playlist
     * @param PaginationService $paginationService
     * @param SongRepository $song
     * @return Response
     */
    #[Route(path: '/playlist/delete/{id}', name: 'playlist_delete')]
    public function delete(Request $request,ManagerRegistry $doctrine, Playlist $playlist)
    {
        if (!$this->isGranted("ROLE_USER") || $this->getUser()->getId() !== $playlist->getUser()->getId()) {
            $this->addFlash("danger", "You are not the owner of this playlist.");
            return $this->redirectToRoute("playlist");
        }
        try {
            $em = $doctrine->getManager();
            $em->remove($playlist);
            $em->flush();
            $this->addFlash("success", "Your playlist is deleted.");
        } catch (Exception $e) {
            $this->addFlash("success", "Can't delete your playlist.");
        }
        return $this->redirectToRoute("playlist");
    }
}
