<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\SongRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SongsController extends AbstractController
{
    /**
     * @Route("/songs", name="songs")
     */
    public function index(SongRepository $songRepository): Response
    {
        return $this->render('songs/index.html.twig', [
            'controller_name' => 'SongsController',
            'songs' => $songRepository->findAll()
        ]);
    }

    /**
     * @Route("/songs/download/{id}", name="song_download")
     */
    public function download(Song $song, SongRepository $songRepository): Response
    {
        return $this->render('songs/index.html.twig', [
            'controller_name' => 'SongsController',
            'songs' => $songRepository->findAll()
        ]);
    }
}
