<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\SongRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/search/{term}", name="api")
     */
    public function index(Request $request, string $term = null, SongRepository $songRepository): Response
    {
        $songsEntities = $songRepository->createQueryBuilder('s')
            ->where('s.name LIKE :search_string')
            ->setParameter('search_string', '%' . $term . '%')
            ->getQuery()->getResult();

        $songs = [];
        /** @var Song $song */
        foreach ($songsEntities as $song) {
            $songs[] = [
                "Id"=>$song->getId(),
                "Name"=>$song->getName(),
                "Author"=>$song->getAuthorName(),
                "Mapper"=>$song->getLevelAuthorName(),
                "Difficulties"=>$song->getSongDifficultiesStr(),
            ];


        }

        return new JsonResponse([
                "Results" => $songs,
                "Count" => count($songs)
            ]
        );
    }
}
