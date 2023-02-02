<?php

namespace App\Controller;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Helper\ChartJsDataSet;
use App\Repository\CountryRepository;
use App\Repository\ScoreHistoryRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongRepository;
use App\Repository\UtilisateurRepository;
use App\Service\GrantedService;
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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{

    #[Route(path: '/user/more-stats', name: 'more_stat')]
    public function moreStats(Request $request,ScoreHistoryRepository $scoreHistoryRepository): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                'success' => false,
                'dataset'   => null
            ], 400);
        }
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();
        /** @var ArrayCollection|ScoreHistory[] $histories */
        $histories = $scoreHistoryRepository->
        createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.songDifficulty = :difficulty')
            ->setParameter('user',$utilisateur)
            ->setParameter('difficulty',$request->get('diff'))
            ->setMaxResults($this->isGranted('ROLE_PREMIUM_LVL1') ? 20 : 6)
            ->setFirstResult(0)
            ->orderBy('s.createdAt',"DESC")->getQuery()->getResult();
        $histories = array_reverse($histories);
        $data= [];

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

        foreach($histories AS $history){
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
            'dataset'   => $data
        ], 200);

    }

    #[Route(path: '/reset/apikey', name: 'reset_apikey')]
    public function resetApiKey(Request $request, UtilisateurRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                'success' => false,
                'value'   => null
            ], 400);
        }
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $user->setApiKey(md5(date('y-m-dH:i') . rand(9000, 100000000) . $user->getUsername()));
        $userRepository->add($user);
        return new JsonResponse([
            'success' => true,
            'value'   => $user->getApiKey()
        ], 200);
    }

    #[Route(path: '/user-profile/{username}', name: 'user_profile')]
    public function profile(Request $request, Utilisateur $utilisateur, PaginationService $paginationService, ScoreRepository $scoreRepository): Response
    {

        if ($this->getUser() !== $utilisateur && !$utilisateur->getIsPublic()) {
            $this->addFlash('warning', "This profile is not public.");
            return $this->redirectToRoute('home');
        }

        $qb = $scoreRepository->createQueryBuilder('s')->where('s.user = :user')->setParameter('user', $utilisateur);

        switch ($request->get('order_by', null)) {
            case 'score':
                $qb->orderBy("s.rawPP", $request->get('order_sort', 'asc') == "asc" ? "asc" : "desc");
                break;
            default:
            case 'date':
                $qb->orderBy("s.createdAt", "DESC");
                break;
        }

        $pagination = $paginationService->setDefaults(15)->process($qb, $request);

        return $this->render('user/partial/song_played.html.twig', [
            'pagination'      => $pagination,
            'user'            => $utilisateur,
            'mapperProfile'   => false,
        ]);
    }

    #[Route(path: '/ajax/countries', name: 'ajax_countries')]
    public function ajaxCountries(Request $request, CountryRepository $countryRepository): Response
    {
        $data = $countryRepository->createQueryBuilder("sc")->select("sc.id AS id, sc.label AS text")->where('sc.label LIKE :search')->setParameter('search', '%' . $request->get('q') . '%')->orderBy('sc.label')->getQuery()->getArrayResult();

        return new JsonResponse([
            'results' => $data
        ]);
    }

    #[Route(path: '/user/progess/{id}/{level}', name: 'user_progress_song')]
    public function progressSong(Request $request, Song $song, string $level, Utilisateur $utilisateur, ScoreHistoryRepository $scoreHistoryRepository): Response
    {
        $hashes = $song->getHashes();

        $scores = $scoreHistoryRepository->createQueryBuilder('score_history')->where('score_history.user = :user')->andWhere("score_history.hash IN (:hashes)")->andWhere("score_history.difficulty = :level")->setParameter("user", $this->getUser())->setParameter("hashes", $hashes)->setParameter("level", $level)->orderBy("score_history.updatedAt", "ASC")->getQuery()->getResult();

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
            'scores'          => $scores,
            "song"            => $song,
            "level"           => $level,
            "labels"          => $labels,
            "data"            => $data,
            "notesHit"        => $notesHit,
        ]);
    }

    #[Route(path: '/mapper-profile/{username}', name: 'mapper_profile')]
    public function mappedProfile(Request $request, ManagerRegistry $doctrine, Utilisateur $utilisateur, SongRepository $songRepository, PaginationService $pagination): Response
    {
        $qb = $doctrine->getRepository(Song::class)
                       ->createQueryBuilder("s")
                       ->where('s.user = :user')
                       ->andWhere('(s.programmationDate <= :now OR s.programmationDate IS NULL)')
                       ->setParameter('now', new DateTime())
                       ->setParameter('user', $utilisateur)
                       ->addSelect('s.voteUp - s.voteDown AS HIDDEN rating')
                       ->groupBy("s.id");


        if ($request->get('display_wip', null) != null) {
            $qb->andWhere("s.wip = true");
        } else {
            $qb->andWhere("s.wip != true");
        }

        $qb->leftJoin('s.songDifficulties', 'song_difficulties');

        if ($request->get('only_ranked', null) != null) {
            $qb->andWhere("song_difficulties.isRanked = true");
        }
        if ($request->get('downloads_filter_difficulties', null)) {
            $qb->leftJoin('song_difficulties.difficultyRank', 'rank');
            switch ($request->get('downloads_filter_difficulties')) {
                case 1:
                    $qb->andWhere('rank.level BETWEEN 1 and 3');
                    break;
                case 2 :
                    $qb->andWhere('rank.level BETWEEN 4 and 7');
                    break;
                case 3 :
                    $qb->andWhere('rank.level BETWEEN 8 and 10');
                    break;
                case 6 :
                    $qb->andWhere('rank.level > 10');
                    break;

                case 5 :
                    $wip = true;
                    break;
            }
        }


        $categories = $request->get('downloads_filter_categories', null);
        if ($categories != null) {
            $qb->leftJoin('s.categoryTags', 't');
            foreach ($categories as $k => $v) {
                $qb->andWhere("t.id = :tag$k")->setParameter("tag$k", $v);
            }
        }

        if ($request->get('downloads_filter_order', null)) {
            switch ($request->get('downloads_filter_order')) {
                case 1:
                    $qb->orderBy('s.voteUp - s.voteDown', 'DESC');
                    break;
                case 2 :
                    $qb->orderBy('s.approximativeDuration', 'DESC');
                    break;

                case 4 :
                    $qb->orderBy('s.name', 'ASC');
                    break;
                case 5 :
                    $qb->orderBy('s.downloads', 'DESC');
                    break;
                case 3:
                default:
                    $qb->orderBy('s.lastDateUpload', 'DESC');
                    break;
            }
        } else {
            $qb->orderBy('s.createdAt', 'DESC');
        }


        if ($request->get('converted_maps', null)) {

            switch ($request->get('converted_maps')) {
                case 1:
                    $qb->andWhere('(s.converted = false OR s.converted IS NULL)');
                    break;
                case 2 :
                    $qb->andWhere('s.converted = true');
                    break;
            }
        }

        if ($request->get('downloads_submitted_date', null)) {

            switch ($request->get('downloads_submitted_date')) {
                case 1:
                    $qb->andWhere('(s.programmationDate >= :last7days)')->setParameter('last7days', (new DateTime())->modify('-7 days'));
                    break;
                case 2 :
                    $qb->andWhere('(s.programmationDate >= :last15days)')->setParameter('last15days', (new DateTime())->modify('-15 days'));
                    break;
                case 3 :
                    $qb->andWhere('(s.programmationDate >= :last45days)')->setParameter('last45days', (new DateTime())->modify('-45 days'));
                    break;
            }
        }
        if ($request->get('not_downloaded', 0) > 0 && $this->isGranted('ROLE_USER')) {
            $qb->leftJoin("s.downloadCounters", 'download_counters')->addSelect("SUM(IF(download_counters.user = :user,1,0)) AS HIDDEN count_download_user")->andHaving("count_download_user = 0")->setParameter('user', $this->getuser());
        }
        $qb->andWhere('s.moderated = true');

        //get the 'type' param (added for ajax search)
        $type = $request->get('type', null);
        //check if this is an ajax request
        $ajaxRequest = $type == 'ajax';
        //remove the 'type' parameter so pagination does not break
        if ($ajaxRequest) {
            $request->query->remove('type');
        }

        if ($request->get('search', null)) {
            $exp = explode(':', $request->get('search'));
            switch ($exp[0]) {
                case 'mapper':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.levelAuthorName LIKE :search_string)')->setParameter('search_string', '%' . $exp[1] . '%');
                    }
                    break;
                case 'artist':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.authorName LIKE :search_string)')->setParameter('search_string', '%' . $exp[1] . '%');
                    }
                    break;
                case 'title':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.name LIKE :search_string)')->setParameter('search_string', '%' . $exp[1] . '%');
                    }
                    break;
                case 'desc':
                    if (count($exp) >= 2) {
                        $qb->andWhere('(s.description LIKE :search_string)')->setParameter('search_string', '%' . $exp[1] . '%');
                    }
                    break;
                default:
                    $qb->andWhere('(s.name LIKE :search_string OR s.authorName LIKE :search_string OR s.description LIKE :search_string OR s.levelAuthorName LIKE :search_string)')->setParameter('search_string', '%' . $request->get('search', null) . '%');
            }
        }
        $qb->andWhere("s.isDeleted != true");

        if ($request->get('onclick_dl')) {
            $ids = $qb->select('s.id')->getQuery()->getArrayResult();
            return $this->redirect("ragnac://install/" . implode('-', array_map(function ($id) {
                    return array_pop($id);
                }, $ids)));
        }

        switch ($request->get('order_by', null)) {
            case 'downloads':
                $qb->orderBy("s.downloads", $request->get('order_sort', 'asc') == "asc" ? "asc" : "desc");
                break;
            case 'upload_date':
                $qb->orderBy("s.programmationDate", $request->get('order_sort', 'asc') == "asc" ? "asc" : "desc");
                break;
            case 'name':
                $qb->orderBy("s.name", $request->get('order_sort', 'asc') == "asc" ? "asc" : "desc");
                break;
            case 'rating':
                $qb->orderBy("rating", $request->get('order_sort', 'asc') == "asc" ? "asc" : "desc");
                break;
            default:
                $qb->orderBy("s.createdAt", "DESC");
                break;
        }

        //$pagination = null;
        //if($ajaxRequest || $request->get('ppage1')) {
        $songs = $pagination->setDefaults(15)->process($qb, $request);

        return $this->render('user/partial/song_mapped.html.twig', [
            'controller_name' => 'UserController',
            'user'            => $utilisateur,
            'categories'      => $categories,
            'songs'           => $songs
        ]);
    }

    #[Route(path: '/user/app-and-premium', name: 'user_applications')]
    public function ApplicationsAndPremium(Request $request, UtilisateurRepository $userRepo)
    {

        $this->PatreonAction($request, $userRepo);

        return $this->render('user/application.html.twig', [

        ]);
    }

    #[Route(path: '/user', name: 'user')]
    public function index(Request $request, ManagerRegistry $doctrine, TranslatorInterface $translator, UtilisateurRepository $utilisateurRepository, ScoreHistoryRepository $scoreHistoryRepository, PaginationService $paginationService): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account!"));
            return $this->redirectToRoute('home');
        }
        $em = $doctrine->getManager();
        if ($this->getUser()->getApiKey() == null) {
            $this->getUser()->setApiKey(md5(date('d/m/Y H:i:s') . $this->getUser()->getUsername()));
        }
        $em->flush();
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $form = $this->createForm(UtilisateurType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
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

        $qb = $scoreHistoryRepository->createQueryBuilder('s')->where('s.user = :user')->setParameter('user', $user)->orderBy('s.createdAt', "desc");
        $pagination = $paginationService->setDefaults(10)->process($qb, $request);

        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
            'pagination'      => $pagination,
            'form'            => $form->createView()
        ]);
    }

    private function PatreonAction(Request $request, UtilisateurRepository $userRepo)
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if ($request->get('code')) {
            $oauth_client = new OAuth($this->getParameter('patreon_client_id'), $this->getParameter('patreon_client_secret'));
            $tokens = $oauth_client->get_tokens($_GET['code'], $this->generateUrl('user_applications', [], UrlGeneratorInterface::ABS_URL));
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
}
