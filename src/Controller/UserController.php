<?php

namespace App\Controller;

use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\SongTemporaryList;
use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Helper\ChartJsDataSet;
use App\Repository\CountryRepository;
use App\Repository\ScoreHistoryRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongCategoryRepository;
use App\Repository\UtilisateurRepository;
use App\Service\SearchService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Patreon\API;
use Patreon\OAuth;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    #[Route(path: '/user/more-stats', name: 'more_stat')]
    public function moreStats(Request $request, ScoreHistoryRepository $scoreHistoryRepository): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                'success' => false,
                'datasets' => null,
            ], 400);
        }
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();
        /** @var ArrayCollection|ScoreHistory[] $histories */
        $histories = $scoreHistoryRepository->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.songDifficulty = :difficulty')
            ->setParameter('user', $utilisateur)
            ->setParameter('difficulty', $request->get('diff'))
            ->setMaxResults($this->isGranted('ROLE_PREMIUM_LVL1') ? 20 : 6)
            ->setFirstResult(0)
            ->orderBy('s.createdAt', "DESC")
            ->getQuery()->getResult();
        $histories = array_reverse($histories);
        $data = [];

        #region missed runes
        $missedRunes = new ChartJsDataSet();
        $missedRunes->setLabel("Missed runes");
        $missedRunes->setBackgroundColor("#ff0000");
        $missedRunes->setBorderColor("#ff0000");
        #endregion

        #region blue combo
        $blueCombos = new ChartJsDataSet();
        $blueCombos->setLabel("Blue combos");
        $blueCombos->setBackgroundColor("#18b3f5");
        $blueCombos->setBorderColor("#18b3f5");
        #endregion

        #region yellow combo
        $yellowCombos = new ChartJsDataSet();
        $yellowCombos->setLabel("Yellow combos");
        $yellowCombos->setBackgroundColor("#f5cc18");
        $yellowCombos->setBorderColor("#f5cc18");
        #endregion

        #region distance
        $distance = new ChartJsDataSet();
        $distance->setLabel("Distance");
        $distance->setBackgroundColor("#781667");
        $distance->setBorderColor("#781667");
        $distance->setYAxisID("y1");
        #endregion

        foreach ($histories as $history) {
            $missedRunes->addData($history->getMissed());
            $blueCombos->addData($history->getComboBlue());
            $yellowCombos->addData($history->getComboYellow());
            $distance->addData($history->getScore());
        }
        $data[] = ($missedRunes->serialize());
        $data[] = ($blueCombos->serialize());
        $data[] = ($yellowCombos->serialize());
        $data[] = ($distance->serialize());

        return new JsonResponse([
            'success' => true,
            'datasets' => ['datasets' => $data],
        ], 200);
    }

    #[Route(path: '/reset/apikey', name: 'reset_apikey')]
    public function resetApiKey(Request $request, UtilisateurRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                'success' => false,
                'value' => null,
            ], 400);
        }
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $user->setApiKey(md5(date('y-m-dH:i').rand(9000, 100000000).$user->getUsername()));
        $userRepository->add($user);

        return new JsonResponse([
            'success' => true,
            'value' => $user->getApiKey(),
        ], 200);
    }

    #[Route(path: '/recently-played/', name: 'recently_played')]
    public function recentlyPlayed(
        Request $request,
        PaginationService $paginationService,
        ScoreHistoryRepository $scoreRepository,
        SongCategoryRepository $songCategoryRepository,
        SearchService $searchService
    ): Response {
        $user = $this->getUser();

        $qb = $scoreRepository
            ->createQueryBuilder('score')
            ->leftJoin('score.songDifficulty', 'song_difficulties')
            ->leftJoin('song_difficulties.song', 'song')
            ->where('score.user = :user')
            ->setParameter('user', $user);

        $filters = $searchService->baseSearchQb($qb, $request);
        $pagination = $paginationService->setDefaults(65)->process($qb, $request);

        return $this->render('user/recently_played.html.twig', [
            'pagination' => $pagination,
            'user' => $user,
            'filters' => $filters,
            'categories' => $songCategoryRepository->findBy([], ['label' => "asc"]),
            'mapperProfile' => false,
        ]);
    }

    #[Route(path: '/user-profile/{username}', name: 'user_profile')]
    public function profile(
        Request $request,
        Utilisateur $utilisateur,
        PaginationService $paginationService,
        ScoreRepository $scoreRepository
    ): Response {
        if ($this->getUser() !== $utilisateur && !$utilisateur->getIsPublic()) {
            $this->addFlash('warning', "This profile is not public.");

            return $this->redirectToRoute('home');
        }

        $qb = $scoreRepository->createQueryBuilder('s')->where('s.user = :user')->setParameter('user', $utilisateur);

        switch ($request->get('order_by', null)) {
            case 'score':
                $qb->orderBy("s.rawPP", $request->get('order_sort', 'asc') == "asc" ? "asc" : "desc");
                break;
            case 'date':
                $qb->orderBy("s.createdAt", "DESC");
                break;
            default:
                $qb->orderBy("s.rawPP", "desc");
                break;
        }

        $pagination = $paginationService->setDefaults(15)->process($qb, $request);

        return $this->render('user/partial/song_played.html.twig', [
            'pagination' => $pagination,
            'user' => $utilisateur,
            'mapperProfile' => false,
        ]);
    }

    #[Route(path: '/ajax/countries', name: 'ajax_countries')]
    public function ajaxCountries(Request $request, CountryRepository $countryRepository): Response
    {
        $data = $countryRepository->createQueryBuilder("sc")->select("sc.id AS id, sc.label AS text")->where(
            'sc.label LIKE :search'
        )->setParameter('search', '%'.$request->get('q').'%')->orderBy('sc.label')->getQuery()->getArrayResult();

        return new JsonResponse([
            'results' => $data,
        ]);
    }

    #[Route(path: '/user/progess/{id}/{level}', name: 'user_progress_song')]
    public function progressSong(
        Request $request,
        Song $song,
        string $level,
        Utilisateur $utilisateur,
        ScoreHistoryRepository $scoreHistoryRepository
    ): Response {
        $hashes = $song->getHashes();

        $scores = $scoreHistoryRepository->createQueryBuilder('score_history')->where(
            'score_history.user = :user'
        )->andWhere("score_history.hash IN (:hashes)")->andWhere("score_history.difficulty = :level")->setParameter(
            "user",
            $this->getUser()
        )->setParameter("hashes", $hashes)->setParameter("level", $level)->orderBy(
            "score_history.updatedAt",
            "ASC"
        )->getQuery()->getResult();

        $labels = [];
        $data = [];
        /** @var ScoreHistory $score */
        foreach ($scores as $score) {
            $labels[] = $score->getUpdatedAt()->format("Y-d-m H:i");
            $data[] = $score->getScore();
            $notesHit[] = $score->getNotesHit() ?? 0;
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

    #[Route(path: '/mapper-profile/{username}', name: 'mapper_profile')]
    public function mappedProfile(
        Request $request,
        ManagerRegistry $doctrine,
        Utilisateur $utilisateur,
        PaginationService $pagination,
        SearchService $searchService,
        SongCategoryRepository $categoryRepository
    ): Response {
        $qb = $doctrine->getRepository(Song::class)
            ->createQueryBuilder("song")
            ->andWhere('mapper.id = :user')
            ->setParameter('now', new DateTime())
            ->setParameter('user', $utilisateur)
            ->addSelect('song.voteUp - song.voteDown AS HIDDEN rating')
            ->groupBy("song.id");

        $searchService->baseSearchQb($qb, $request);

        if ($request->get('oneclick_dl')) {
            $songs = $qb->getQuery()->getResult();
            $list = new SongTemporaryList();

            $em = $doctrine->getManager();
            foreach ($songs as $song) {
                $list->addSong($song);
            }
            $em->persist($list);
            $em->flush();

            return $this->redirect("ragnac://list/".$list->getId());
        }

        $songs = $pagination->setDefaults(15)->process($qb, $request);
        $categories = $categoryRepository
            ->createQueryBuilder("c")
            ->leftJoin("c.songs", 's')
            ->where('c.isOnlyForAdmin != true')
            ->andWhere("s.id is not null")
            ->orderBy('c.label')
            ->getQuery()
            ->getResult();

        return $this->render('user/partial/song_mapped.html.twig', [
            'controller_name' => 'UserController',
            'user' => $utilisateur,
            'categories' => $categories,
            'songs' => $songs,
        ]);
    }

    #[Route(path: '/user/app-and-premium', name: 'user_applications')]
    public function ApplicationsAndPremium(Request $request, UtilisateurRepository $userRepo)
    {
        $this->PatreonAction($request, $userRepo);

        return $this->render('user/application.html.twig', []);
    }

    private function PatreonAction(Request $request, UtilisateurRepository $userRepo)
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if ($request->get('code')) {
            $oauth_client = new OAuth(
                $this->getParameter('patreon_client_id'),
                $this->getParameter('patreon_client_secret')
            );
            $tokens = $oauth_client->get_tokens(
                $_GET['code'],
                $this->generateUrl('user_applications', [], UrlGeneratorInterface::ABS_URL)
            );
            if (!isset($tokens['error'])) {
                $access_token = $tokens['access_token'];
                $refresh_token = $tokens['refresh_token'];
// Here, you should save the access and refresh tokens for this user somewhere.
// Conceptually this is the point either you link an existing user of your app with his/her Patreon account,
// or, if the user is a new user, create an account for him or her in your app, log him/her in,
// and then link this new account with the Patreon account.
// More or less a social login logic applies here.

                $user->setPatreonAccessToken($access_token);
                $user->setPatreonRefreshToken($refresh_token);
                // Here you can decode the state var returned from Patreon,
                // and use the final redirect url to redirect your user to the relevant unlocked content or feature in your site/app.
                $api_client = new API($user->getPatreonAccessToken());
                $current_member = $api_client->fetch_user();


                $userRepo->add($user);
            }
        }
        if ($user->getPatreonAccessToken()) {
            try {
                $api_client = new API($user->getPatreonAccessToken());
                $user->setPatreonData(json_encode($api_client->fetch_user()));
                $userRepo->add($user);
            } catch (Exception $e) {
            }
        }
        if (!isset($api_client)) {
            return;
        }
        $current_member = $api_client->fetch_user();

        if ($current_member != null && isset($current_member['data']) && isset($current_member['data']['included'])) {
            $attrs = $current_member['data']['included']['attributes'];
            if (count($attrs) > 0) {
                $attr = array_pop($attrs);
                if ($attr["patron_status"] == "active_patron") {
                    switch ($attr["currently_entitled_amount_cents"]) {
                        case 600:
                            $user->addRole('ROLE_PREMIUM_LVL3');
                            break;
                        case 300:
                            $user->addRole('ROLE_PREMIUM_LVL2');
                            break;
                        case 100:
                            $user->addRole('ROLE_PREMIUM_LVL1');
                            break;
                    }
                } else {
                    $user->removeRole('ROLE_PREMIUM_LVL3');
                    $user->removeRole('ROLE_PREMIUM_LVL2');
                    $user->removeRole('ROLE_PREMIUM_LVL1');
                }
                $userRepo->add($user);
            }
        }
    }

    #[Route(path: '/user', name: 'user')]
    public function index(
        Request $request,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        UtilisateurRepository $utilisateurRepository,
        ScoreHistoryRepository $scoreHistoryRepository,
        UserPasswordHasherInterface $passwordEncoder,
        PaginationService $paginationService
    ): Response {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account!"));

            return $this->redirectToRoute('home');
        }

        if ($this->getUser()->getApiKey() == null) {
            $this->getUser()->setApiKey(md5(date('d/m/Y H:i:s').$this->getUser()->getUsername()));
        }

        $em = $doctrine->getManager();
        $em->flush();
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $form = $this->createForm(UtilisateurType::class, $user);
        $previousUsername = $this->getUser()->getUserIdentifier();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($previousUsername !== $user->getUsername()) {
                $exists = $utilisateurRepository->findBy(['username' => $user->getUsername()]);

                if ($exists) {
                    $this->addFlash('danger', 'This username is already used by someone else!');
                    $user->setUsername($previousUsername);
                }
            }

            if ($form->has('currentPassword') && !empty($form->get('currentPassword')->getData())) {
                if ($passwordEncoder->isPasswordValid($user, $form->get('currentPassword')->getData())) {
                    // Encode the plain password, and set it.
                    $encodedPassword = $passwordEncoder->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    );

                    $user->setPassword($encodedPassword);
                } else {
                    $this->addFlash('danger', 'Wrong current password. your password isn\'t changed');
                }
            }

            if (!$this->isGranted('ROLE_PREMIUM_LVL2')) {
                $user->setUsernameColor("#ffffff");
            }

            $email_user = $utilisateurRepository->findOneBy(['email' => $user->getEmail()]);

            if ($email_user != null && $user->getId() !== $email_user->getId()) {
                $form->addError(new FormError("This email is already used."));
            } else {
                $email_user = $utilisateurRepository->findOneBy(['mapper_name' => $user->getMapperName()]);

                if ($email_user != null && $user->getId() !== $email_user->getId()) {
                    $form->addError(new FormError("This mapper name is already used."));
                } else {
                    $doctrine->getManager()->flush();
                }
            }
        }

        $qb = $scoreHistoryRepository->createQueryBuilder('s')->where('s.user = :user')->setParameter(
            'user',
            $user
        )->orderBy('s.createdAt', "desc");
        $pagination = $paginationService->setDefaults(10)->process($qb, $request);

        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
            'pagination' => $pagination,
            'form' => $form->createView(),
        ]);
    }
}
