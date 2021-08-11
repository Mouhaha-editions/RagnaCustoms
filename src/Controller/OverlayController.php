<?php

namespace App\Controller;

use App\Entity\Overlay;
use App\Repository\OverlayRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OverlayController extends AbstractController
{
    /**
     * @Route("/overlay/display/{api}", name="overlay")
     */
    public function index(string $api): Response
    {

        return $this->render('overlay/index.html.twig', [
            'controller_name' => 'OverlayController',
            'apiKey' => $api
        ]);
    }

    /**
     * @Route("/overlay/details/{apikey}", name="overlay_details")
     */
    public function detail(string $apikey, OverlayRepository $overlayRepository): Response
    {
        /** @var Overlay $overlay */
        $overlay = $overlayRepository->createQueryBuilder("o")
            ->leftJoin('o.user', "user")
            ->where('user.apiKey = :api')
            ->setParameter('api', $apikey)
            ->setMaxResults(1)->setFirstResult(0)
            ->getQuery()->getOneOrNullResult();
        if ($overlay != null && $overlay->getDifficulty() != null) {
            $song = $overlay->getDifficulty()->getSong();
            $diff = $overlay->getDifficulty();
            return new JsonResponse([
                "enabled" => true,
                "cover" => "/covers/" . $song->getId() . $song->getCoverImageExtension(),
                'title' => $song->getName() . " level " . $diff->getDifficultyRank()->getLevel(),
                'mapper' => $song->getLevelAuthorName(),
                'author' => $song->getAuthorName()

            ]);
        } else {
            return new JsonResponse([
                "enabled" => false,
                "cover" => "",
                'title' => "",
                'mapper' => "",
                'author' => ""
            ]);
        }
    }
}
