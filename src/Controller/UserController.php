<?php

namespace App\Controller;

use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\SongHash;
use App\Entity\Utilisateur;
use App\Enum\EGamification;
use App\Form\UtilisateurType;
use App\Repository\ScoreHistoryRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongHashRepository;
use App\Repository\UtilisateurRepository;
use App\Service\GamificationService;
use App\Service\StatisticService;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/user/{id}", name="user_profile")
     * @param Request $request
     * @param Utilisateur $utilisateur
     * @param PaginationService $paginationService
     * @param StatisticService $statisticService
     * @param ScoreRepository $scoreRepository
     * @param ScoreHistoryRepository $scoreHistoryRepository
     * @param UtilisateurRepository $utilisateurRepository
     * @param GamificationService $gamificationService
     * @return Response
     */
    public function profile(Request $request, Utilisateur $utilisateur,
                            PaginationService $paginationService,
                            StatisticService $statisticService,
                            ScoreRepository $scoreRepository,
                            ScoreHistoryRepository $scoreHistoryRepository,
                            UtilisateurRepository $utilisateurRepository,
                            GamificationService $gamificationService
    ): Response
    {
        $this->gamification($utilisateur,$statisticService,$gamificationService, $scoreRepository);


        $qb =  $scoreHistoryRepository->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $utilisateur)
            ->orderBy('s.updatedAt', "desc");
        $pagination =  $paginationService->setDefaults(25)->process($qb, $request);

        return $this->render('user/partial/song_played.html.twig', [
            'controller_name' => 'UserController',
            'pagination' => $pagination,
            'user' => $utilisateur,
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
            $labels[] = $score->getUpdatedAt()->format("Y-d-m H:i");
            $data[] = $score->getScore();
            $notesHit[] = $score->getNotesHit()??0;
        }


        return $this->render('user/progress.html.twig', [
            'controller_name' => 'UserController',
            'scores' => $scores,
            "song" => $song,
            "level" => $level,
            "labels" => $labels,
            "data" => $data,
            "notesHit" => $notesHit,
        ]);
    }

    /**
     * @Route("/user/mapped/{id}", name="user_mapped_profile")
     */
    public function mappedProfile(Request $request, Utilisateur $utilisateur,
                                  GamificationService $gamificationService, StatisticService $statisticService,ScoreRepository $scoreRepository): Response
    {
        $this->gamification($utilisateur,$statisticService,$gamificationService, $scoreRepository);

        return $this->render('user/partial/song_mapped.html.twig', [
            'controller_name' => 'UserController',
            'user' => $utilisateur
        ]);
    }

    /**
     * @Route("/user", name="user")
     */
    public function index(Request $request, TranslatorInterface $translator,
                          UtilisateurRepository $utilisateurRepository, ScoreHistoryRepository $scoreHistoryRepository,
    PaginationService $paginationService): Response
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

        $qb =  $scoreHistoryRepository->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.updatedAt', "desc");
        $pagination =  $paginationService->setDefaults(25)->process($qb, $request);


        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
            'pagination'=>$pagination,
            'form' => $form->createView()
        ]);
    }

    private function gamification(Utilisateur $utilisateur, StatisticService $statisticService,
                                  GamificationService $gamificationService, ScoreRepository $scoreRepository)
    {

        #region distances
        if ($statisticService->getTotalDistance($utilisateur) >= 50000) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_DISTANCE_1, $utilisateur);
        }
        if ($statisticService->getTotalDistance($utilisateur) >= 100000) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_DISTANCE_2, $utilisateur);
        }
        if ($statisticService->getTotalDistance($utilisateur) >= 1000000) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_DISTANCE_3, $utilisateur);
        }
        if ($statisticService->getTotalDistance($utilisateur) >= 5000000) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_DISTANCE_4, $utilisateur);
        }
        #endregion

        #region songs
        $result = $scoreRepository->createQueryBuilder('s')
            ->select("COUNT(DISTINCT(s.hash)) AS nb")
            ->where('s.user = :user')
            ->setParameter('user', $utilisateur)
            ->setFirstResult(0)->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
        $count = $result['nb'];
        if ($count >= 500) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_SONG_COUNT_4, $utilisateur);
        }
        if ($count >= 150) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_SONG_COUNT_3, $utilisateur);
        }
        if ($count >= 50) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_SONG_COUNT_2, $utilisateur);
        }
        if ($count >= 25) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_SONG_COUNT_1, $utilisateur);
        }
        #endregion

        #region mapper
        if ($utilisateur->getSongs()->count() >= 50) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_MAP_SONG_4, $utilisateur);
        }
        if ($utilisateur->getSongs()->count() >= 15) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_MAP_SONG_3, $utilisateur);
        }
        if ($utilisateur->getSongs()->count() >= 5) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_MAP_SONG_2, $utilisateur);
        }
        if ($utilisateur->getSongs()->count() >= 1) {
            $gamificationService->unlock(EGamification::ACHIEVEMENT_MAP_SONG_1, $utilisateur);
        }

        #endregion
        $gamificationService->reset();
    }


}
