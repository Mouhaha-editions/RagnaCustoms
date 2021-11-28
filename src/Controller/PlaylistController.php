<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\Song;
use App\Form\PlaylistType;
use App\Repository\PlaylistRepository;
use App\Repository\SongRepository;
use Exception;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlaylistController extends AbstractController
{
    /**
     * @Route("/playlists", name="playlist")
     */
    public function index(): Response
    {
        /** @var Playlist[] $playlists */
        $playlists = $this->getUser()->getPlaylists();

        return $this->render('playlist/index.html.twig', [
            'playlists' => $playlists,
        ]);
    }

    /**
     * @Route("/playlist/show/{id}", name="playlist_show")
     * @param Request $request
     * @param Playlist $playlist
     * @param PaginationService $paginationService
     * @param SongRepository $songRepository
     * @return Response
     */

    public function show(Request $request, Playlist $playlist, PaginationService $paginationService,
                         SongRepository $songRepository): Response
    {
        if (!$playlist->getIsPublic()) {
            $this->addFlash("warning", "This playlist is not public");
            return $this->redirectToRoute('home');
        }
        $qb = $songRepository->createQueryBuilder("s")
            ->leftJoin("s.playlists", 'playlist')
            ->where('playlist = :playlist')
            ->setParameter("playlist", $playlist)
            ->addOrderBy('s.name');
        if ($request->get('onclick_dl')) {
            $ids = $qb->select('s.id')->getQuery()->getArrayResult();
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


    /**
     * @Route("/playlist/remove", name="playlist_remove")
     * @param Request $request
<<<<<<< HEAD
=======
     * @param PlaylistRepository $playlistRepository
     * @param SongRepository $songRepository
>>>>>>> 17ebaef6adaa1b59fb3003266577b0c437faf9eb
     * @return Response
     */
    public function remove(Request $request,PlaylistRepository $playlistRepository, SongRepository $songRepository)
    {
        $playlist = $playlistRepository->find($request->get('playlist_id'));
        if (!$this->isGranted("ROLE_USER") || $this->getUser()->getId() !== $playlist->getUser()->getId()) {
            $this->addFlash("danger", "You are not the owner of this playlist.");
            return new Response("",500);
        }
        $song = $songRepository->find($request->get('id'));
        $playlist->removeSong($song);
        $this->getDoctrine()->getManager()->flush();
        return new JsonResponse();
    }

    /**
     * @Route("/playlist/edit/{id}", name="playlist_edit")
     * @param Request $request
     * @param Playlist $playlist
     * @param SongRepository $songRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    public function edit(Request $request, Playlist $playlist, SongRepository $songRepository, PaginationService $paginationService)
    {
        if (!$this->isGranted("ROLE_USER") || $this->getUser()->getId() !== $playlist->getUser()->getId()) {
            $this->addFlash("danger", "You are not the owner of this playlist.");
            return $this->redirectToRoute("playlist");
        }
        $form = $this->createForm(PlaylistType::class, $playlist);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success',"Playlist edited!");
        }

        $qb = $songRepository->createQueryBuilder("s")
            ->leftJoin("s.playlists", 'playlist')
            ->where('playlist = :playlist')
            ->setParameter("playlist", $playlist)
            ->addOrderBy('s.name');
        if ($request->get('onclick_dl')) {
            $ids = $qb->select('s.id')->getQuery()->getArrayResult();
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
     * @Route("/playlist/delete/{id}", name="playlist_delete")
     * @param Request $request
     * @param Playlist $playlist
     * @param PaginationService $paginationService
     * @param SongRepository $song
     * @return Response
     */
    public function delete(Request $request, Playlist $playlist)
    {
        if (!$this->isGranted("ROLE_USER") || $this->getUser()->getId() !== $playlist->getUser()->getId()) {
            $this->addFlash("danger", "You are not the owner of this playlist.");
            return $this->redirectToRoute("playlist");
        }
        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($playlist);
            $em->flush();
            $this->addFlash("success", "Your playlist is deleted.");
        } catch (Exception $e) {
            $this->addFlash("success", "Can't delete your playlist.");
        }
        return $this->redirectToRoute("playlist");
    }
}
