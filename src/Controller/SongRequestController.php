<?php

namespace App\Controller;

use App\Entity\SongRequest;
use App\Entity\SongRequestVote;
use App\Entity\Utilisateur;
use App\Form\SongRequestFormType;
use App\Repository\SongRequestRepository;
use App\Repository\SongRequestVoteRepository;
use App\Service\DiscordService;
use Doctrine\Persistence\ManagerRegistry;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

//#[Route(path: '/song-request', name: 'song_request')]
class SongRequestController extends AbstractController
{
    /**
     * @param Request $request
     * @param SongRequestRepository $songRequestRepository
     * @param PaginationService $pagination
     * @param DiscordService $discordService
     * @return Response
     */
//    #[Route(path: '/delete/{id}', name: '_delete')]
    public function delete(Request $request, SongRequest $songRequest, SongRequestRepository $songRequestRepository): Response
    {
        if ($songRequest->getRequestedBy() == $this->getUser() || $this->isGranted('ROLE_MODERATOR')) {
            $songRequestRepository->remove($songRequest);
            $this->addFlash('danger', "Request deleted");
        } else {
            $this->addFlash('danger', "this request can't be deleted");
        }
        return $this->redirect($this->generateUrl('song_request_index')."#your-song");
    }

    /**
     * @param Request $request
     * @param SongRequestRepository $songRequestRepository
     * @param PaginationService $pagination
     * @param DiscordService $discordService
     * @return Response
     */
//    #[Route(path: '/', name: '_index')]
    public function index(Request $request, ManagerRegistry $doctrine, SongRequestRepository $songRequestRepository, PaginationService $pagination, DiscordService $discordService): Response
    {
        $form = null;
        if ($this->isGranted('ROLE_USER')) {
            $save = true;
            $songReq = new SongRequest();
            /** @var Utilisateur $user */
            $user = $this->getUser();

            $songReq->setRequestedBy($user);
            $form = $this->createForm(SongRequestFormType::class, $songReq, ['attr' => ["class" => "form-horizontal"]]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                if ($user->getOpenSongRequests()->count() >= 3) {
                    $this->addFlash('warning', 'You already have 3 or more requests, please wait before adding a new one.');
                    $save = false;
                } elseif ($user->getCredits() < 30) {
                    $this->addFlash('warning', 'You need 30 credits to add a song request,play more songs to earn credits ;)');
                    $save = false;
                } else {
                    $em = $doctrine->getManager();
                    $em->persist($songReq);
                    $em->flush();
                    $discordService->sendRequestSongMessage($songReq);
                    return $this->redirectToRoute("song_request_index");
                }
            }
        }
        $qb = $songRequestRepository->createQueryBuilder('sr');
        $qb->select("sr");
        $qb->leftJoin("sr.requestedBy", 'u');
        $qb->where('sr.state IN (:displayable)')->setParameter('displayable', [
            SongRequest::STATE_ASKED,
            SongRequest::STATE_IN_PROGRESS
        ]);
        switch ($request->get('order')) {
            case 1:
                $qb->addSelect('COUNT(v.id) AS HIDDEN count_votes');
                $qb->leftJoin('sr.songRequestVotes', 'v');
                $qb->groupBy("sr.id");
                $qb->orderBy("count_votes", "DESC")->addOrderBy("IF(u.isPatreon = true,1,0)", "DESC")->addOrderBy("sr.createdAt", 'DESC');
                break;
            default:
                $qb->orderBy("IF(u.isPatreon = true,1,0)", "DESC")->addOrderBy("sr.createdAt", 'DESC');
        }

        $search = $request->get('search');
        if ($search != null) {
            $qb->andWhere('(sr.title LIKE :search_string OR sr.author LIKE :search_string)')->setParameter('search_string', '%' . $search . '%');
        } else {
            $form = null;
        }

        $songRequests = $pagination->setDefaults(50)->process($qb, $request);
        $reason = "";
        if ($this->isGranted('ROLE_USER')) {

            if ($user->getOpenSongRequests()->count() >= 3) {
                $reason = '<div class="alert d-none" data-type="info"  data-title="Too much requests">You already have 3 or more requests, please wait before adding a new one.</div>';
                $save = false;
            } elseif ($user->getCredits() < 30) {
                $reason = '<div class="alert d-none"  data-type="info"  data-title="Not enough credits">You need 30 credits to add a song request, play more songs to earn credits ;)</div>';
                $save = false;
            }
        }
        return $this->render('song_request/index.html.twig', [
            'songRequests' => $songRequests,
            'form' => $form && $save ? $form->createView() : null,
            "reason" => $reason
        ]);
    }

    /**
     * @param Request $request
     * @param SongRequest $songRequest
     * @param TranslatorInterface $translator
     * @return Response
     */
//    #[Route(path: '/claim/{id}', name: '_claim')]
    public function claim(Request $request, ManagerRegistry $doctrine, SongRequest $songRequest, TranslatorInterface $translator): Response
    {
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account!"));
            return $this->redirectToRoute('home');
        }
        /** @var Utilisateur $user */
        $user = $this->getUser();
        //not a mapper
        if (!$user->getIsMapper()) {
            $this->addFlash('danger', $translator->trans("You need to be a mapper!"));
            return $this->redirectToRoute('user');
        }
        //song already get a mapper
        if ($songRequest->getMapper()) {
            $this->addFlash('danger', $translator->trans("This request is already claimed!"));
            return $this->redirectToRoute('song_request_index');
        }
        //mapper already is mapping something
        if ($user->getSongRequestInProgress() !== null) {
            $this->addFlash('danger', $translator->trans("You already claimed a request, one thing at a time please."));
            return $this->redirectToRoute('song_request_index');
        }

        $songRequest->addMapperOnIt($user);
        $songRequest->setState(SongRequest::STATE_IN_PROGRESS);
        $doctrine->getManager()->flush();
        $this->addFlash('success', "Song request claimed!");
        return $this->redirectToRoute('song_request_index');
    }

    /**
     * @param Request $request
     * @param SongRequest $songRequest
     * @param TranslatorInterface $translator
     * @return Response
     */
//    #[Route(path: '/unclaim/{id}', name: '_unclaim')]
    public function unclaim(Request $request, ManagerRegistry $doctrine, SongRequest $songRequest, TranslatorInterface $translator): Response
    {
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account!"));
            return $this->redirectToRoute('home');
        }
        /** @var Utilisateur $user */
        $user = $this->getUser();
        //not a mapper
        if (!$user->getIsMapper()) {
            $this->addFlash('danger', $translator->trans("You need to be a mapper!"));
            return $this->redirectToRoute('user');
        }


        if ($songRequest->getMapper() !== $user) {
            $this->addFlash('danger', $translator->trans("You are not the mapper that claim this request."));
            return $this->redirectToRoute('song_request_index');
        }
        $songRequest->removeMapperOnIt($user);
        $songRequest->setState(SongRequest::STATE_ASKED);
        $doctrine->getManager()->flush();
        $this->addFlash('success', "Song request unclaimed!");
        return $this->redirectToRoute('song_request_index');
    }

    /**
     * @param Request $request
     * @param SongRequest $songRequest
     * @param SongRequestVoteRepository $songRequestVoteRepository
     * @param TranslatorInterface $translator
     * @return Response
     */
//    #[Route(path: '/toggle/one/{id}', name: '_toggle')]
    public function toggleOne(Request $request, ManagerRegistry $doctrine, SongRequest $songRequest, SongRequestVoteRepository $songRequestVoteRepository, TranslatorInterface $translator): Response
    {
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account!"));
            return $this->redirectToRoute('home');
        }

        $vote = $songRequestVoteRepository->findOneBy([
            'user' => $this->getUser(),
            'songRequest' => $songRequest
        ]);
        $em = $doctrine->getManager();

        if ($vote == null) {
            $vote = new SongRequestVote();
            $vote->setSongRequest($songRequest);
            $vote->setUser($this->getUser());
            $em->persist($vote);
        } else {
            $em->remove($vote);
        }
        $em->flush();

        return $this->redirectToRoute('song_request_index');
    }
}
