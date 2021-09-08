<?php

namespace App\Controller;

use App\Entity\Overlay;
use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Repository\OverlayRepository;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use DateTime;
use DoctrineExtensions\Query\Mysql\Over;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

class OverlayController extends AbstractController
{
    /**
     * @Route("/overlay/display/{api}", name="overlay")
     */
    public function index(string $api, OverlayRepository $overlayRepository): Response
    {
        /** @var Overlay $overlay */
        $overlay = $overlayRepository->createQueryBuilder('overlay')
            ->leftJoin('overlay.user', 'user')
            ->where('user.apiKey = :apiKey')
            ->setParameter('apiKey', $api)
            ->getQuery()->getOneOrNullResult();

        return $this->render('overlay/index.html.twig', [
            'controller_name' => 'OverlayController',
            'overlay' => $overlay,
            'apiKey' => $api,

        ]);
    }

    /**
     * @Route("/overlay/reset/", name="overlay_reset")
     */
    public function editorReset(SongRepository $songRepository, OverlayRepository $overlayRepository)
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute("home");
        }

        /** @var Overlay $overlay */
        $overlay = $overlayRepository->createQueryBuilder('overlay')
            ->where('overlay.user = :user')
            ->setParameter('user', $this->getUser())
            ->getQuery()->getOneOrNullResult();

        $overlay->setHtml(null);
        $overlay->setCss(null);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        $this->addFlash('success',"You overlay is reset to default.");
        return $this->redirectToRoute('home');
    }

    /**
     *
     * @Route("/overlay/editor/", name="overlay_editor")
     */
    public function editor(SongRepository $songRepository, OverlayRepository $overlayRepository)
    {

        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute("home");
        }
        /** @var Song[] $songs */
        $songs = $songRepository->createQueryBuilder("s")
            ->where('s.moderated = true')
            ->andWhere('s.wip != true')
            ->andWhere('s.isDeleted != true')
            ->orderBy("s.name")->getQuery()->getResult();
        /** @var Overlay $overlay */
        $overlay = $overlayRepository->createQueryBuilder('overlay')
            ->where('overlay.user = :user')
            ->setParameter('user', $this->getUser())
            ->getQuery()->getOneOrNullResult();

        return $this->render('overlay/editor.html.twig', [
            "songs" => $songs,
            "overlay" => $overlay,
        ]);
    }

    /**
     * @Route("/overlay/editor/save", name="overlay_editor_save")
     */
    public function editorSave(Request $request, OverlayRepository $overlayRepository)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute("home");
        }
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $overlay = $overlayRepository->findOneBy(['user' => $user]);
        if ($overlay == null) {
            $overlay = new Overlay();
            $overlay->setUser($user);
            $em->persist($overlay);
        }
        $overlay->setCss($request->get('css'));
        $overlay->setHtml($request->get('html'));
        $em->flush();

        return new JsonResponse([]);
    }

    /**
     * @Route("/overlay/details/{apikey}", name="overlay_details")
     */
    public function detail(Request $request, string $apikey, OverlayRepository $overlayRepository, SongDifficultyRepository $songDifficultyRepository): Response
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

            $tempPasse = "0:00";
            if ($overlay->getStartAt()) {
                $x = (new DateTime())->diff($overlay->getStartAt(), true);
                $tempPasse = $x->i . ":" . str_pad($x->s, 2, "0", STR_PAD_LEFT);
            }
            return new JsonResponse([
                "enabled" => true,
                "cover" => "/covers/" . $song->getId() . $song->getCoverImageExtension(),
                'title' => $song->getName(),
                'level' => $diff->getDifficultyRank()->getLevel(),
                'mapper' => $song->getLevelAuthorName(),
                'author' => $song->getAuthorName(),
                'duration' => $tempPasse . " / " . $song->getApproximativeDurationMin()

            ]);
        } else {
            return new JsonResponse([
                "enabled" => false,
                "cover" => "",
                'title' => "",
                'level' => "",
                'mapper' => "",
                'author' => "",
                'duration' => ""
            ]);
        }
    }
}
