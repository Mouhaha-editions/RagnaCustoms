<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\SongRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class XmlController extends AbstractController
{
    private $paginate = 51;


    #[Route(path: '/songs.xml', name: 'sitemap_songs')]
    public function sitemap(SongRepository $songRepository)
    {
        $artists = $songRepository->createQueryBuilder('s')
            ->select('COUNT(Distinct(s.authorName))')
            ->where("s.moderated = true")
            ->andWhere("s.wip = false")
            ->andWhere('s.isDeleted = false')->getQuery()
            ->getOneOrNullResult();
        return $this->render('sitemap/index.html.twig', [
            'songs' => $songRepository->count([
                    'moderated' => true,
                    "wip" => false,
                    "isDeleted" => false,
                ]) / $this->paginate,
            'artists' => array_pop($artists) / $this->paginate
        ]);
    }

    /**
     * @param SongRepository $songRepository
     * @return Response
     */
    #[Route(path: '/artists-{page}.xml', name: 'sitemap_artists_page')]
    public function sitemapArtistsPage(SongRepository $songRepository, int $page)
    {
        return $this->render('sitemap/artists.html.twig', [
            'songs' => $songRepository->createQueryBuilder('s')
                ->where("s.moderated = true")
                ->andWhere("s.wip = false")
                ->andWhere('s.isDeleted = false')
                ->groupBy('s.authorName')
                ->orderBy('s.authorName')
                ->setFirstResult($page * $this->paginate)
                ->setMaxResults($this->paginate)
                ->getQuery()->getResult()
        ]);
    }

    /**
     * @param SongRepository $songRepository
     * @return Response
     */
    #[Route(path: '/songs-{page}.xml', name: 'sitemap_songs_page')]
    public function sitemapSongsPage(SongRepository $songRepository, int $page)
    {
        return $this->render('sitemap/songs.html.twig', [
            'songs' => $songRepository->createQueryBuilder('s')
                ->where("s.moderated = true")
                ->andWhere("s.wip = false")
                ->andWhere('s.isDeleted = false')
                ->setFirstResult($page * $this->paginate)
                ->setMaxResults($this->paginate)
                ->orderBy('s.name')
                ->getQuery()->getResult()
        ]);
    }

    #[Route(path: '/rss.xml', name: 'rss_song')]
    public function rss(SongRepository $songRepository)
    {
        $songs = $songRepository->findBy([
            'moderated' => true,
            "wip" => false
        ], ['createdAt' => "Desc"]);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        /** @var ArrayCollection|Song[] $songs */
        return $this->render('rss/index.html.twig', [
            'songs' => $songs
        ], $response);
    }
}
