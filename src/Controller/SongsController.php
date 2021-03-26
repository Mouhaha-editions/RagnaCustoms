<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\SongRepository;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class SongsController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(Request $request,SongRepository $songRepository, PaginationService $paginationService): Response
    {
        $qb = $this->getDoctrine()->getRepository(Song::class)->createQueryBuilder("s")
            ;
        if($request->get('downloads_filter_difficulties', null)){
            $qb->leftJoin('s.songDifficulties','song_difficulties')
                ->leftJoin('song_difficulties.difficultyRank','rank');
            switch($request->get('downloads_filter_difficulties')){
                case 1:
                    $qb->where('rank.level BETWEEN 1 and 3');
                    break;
                case 2 :
                    $qb->where('rank.level BETWEEN 4 and 7');
                    break;
                case 3 :
                    $qb->where('rank.level BETWEEN 8 and 10');

                    break;
            }
        }
        $pagination = $paginationService->setDefaults(40)->process($qb,$request);

        return $this->render('songs/index.html.twig', [
            'controller_name' => 'SongsController',
            'songs' => $pagination
        ]);
    }

    /**
     * @Route("/songs/download/{id}", name="song_download")
     */
    public function download(Song $song, SongRepository $songRepository, KernelInterface $kernel): Response
    {
        $em = $this->getDoctrine()->getManager();
        $song->setDownloads($song->getDownloads() + 1);
        $em->flush();
        $fileContent = file_get_contents($kernel->getProjectDir() . "/public/tmp-song/" . $song->getId() . ".zip");
        $response = new Response($fileContent);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $song->getId() . '.zip'
        );
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }
}
