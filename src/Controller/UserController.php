<?php

namespace App\Controller;

use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\SongHash;
use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\ScoreHistoryRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/user/{id}", name="user_profile")
     */
    public function profile(Request $request, Utilisateur $utilisateur, TranslatorInterface $translator, UtilisateurRepository $utilisateurRepository): Response
    {

        return $this->render('user/partial/song_played.html.twig', [
            'controller_name' => 'UserController',
            'user' => $utilisateur
        ]);
    }

    /**
     * @Route("/user/progess/{id}/{level}", name="user_progress_song")
     */
    public function progressSong(Request $request, Song $song, string $level, Utilisateur $utilisateur,
                                 ScoreHistoryRepository $scoreHistoryRepository): Response
    {
        $hashes = $song->getHashes();

        $scores = $scoreHistoryRepository->createQueryBuilder('score_history')
            ->where('score_history.user = :user')
            ->andWhere("score_history.hash IN (:hashes)")
            ->andWhere("score_history.difficulty = :level")
            ->setParameter("user", $this->getUser())
            ->setParameter("hashes", $hashes)
            ->setParameter("level", $level)
            ->orderBy("score_history.updatedAt", "ASC")
            ->getQuery()->getResult();

        $labels = [];
        $data = [];
        /** @var ScoreHistory $score */
        foreach ($scores as $score) {
            $labels[]=$score->getUpdatedAt()->format("Y-d-m H:i");
            $data[]=$score->getScore();
        }


        return $this->render('user/progress.html.twig', [
            'controller_name' => 'UserController',
            'scores' => $scores,
            "song" => $song,
            "level" => $level,
            "labels" => $labels,
            "data" => $data,
        ]);
    }

    /**
     * @Route("/user/mapped/{id}", name="user_mapped_profile")
     */
    public function mappedProfile(Request $request, Utilisateur $utilisateur, TranslatorInterface $translator, UtilisateurRepository $utilisateurRepository): Response
    {

        return $this->render('user/partial/song_mapped.html.twig', [
            'controller_name' => 'UserController',
            'user' => $utilisateur
        ]);
    }

    /**
     * @Route("/user", name="user")
     */
    public function index(Request $request, TranslatorInterface $translator, UtilisateurRepository $utilisateurRepository): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account to access this page."));
            return $this->redirectToRoute('home');
        }
        $em = $this->getDoctrine()->getManager();
        if ($this->getUser()->getApiKey() == null) {
            $this->getUser()->setApiKey(md5(date('d/m/Y H:i:s') . $this->getUser()->getUsername()));
        }
        $em->flush();
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $form = $this->createForm(UtilisateurType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $email_user = $utilisateurRepository->findOneBy(['email' => $user->getEmail()]);
            if ($email_user != null && $user->getId() !== $email_user->getId()) {
                $form->addError(new FormError("This email is already used."));
            } else {
                $email_user = $utilisateurRepository->findOneBy(['mapper_name' => $user->getMapperName()]);
                if ($email_user != null && $user->getId() !== $email_user->getId()) {
                    $form->addError(new FormError("This mapper name is already used."));
                } else {
                    $this->getDoctrine()->getManager()->flush();
                }
            }
        }

        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
            'form' => $form->createView()
        ]);
    }


}
