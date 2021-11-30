<?php

namespace App\Controller;

use App\Entity\SongRequest;
use App\Entity\SongRequestVote;
use App\Entity\Utilisateur;
use App\Form\SongRequestFormType;
use App\Repository\SongRequestRepository;
use App\Repository\SongRequestVoteRepository;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/song-request", name="song_request")
 */
class SongRequestController extends AbstractController
{
    /**
     * @Route("/", name="_index")
     * @param Request $request
     * @param SongRequestRepository $songRequestRepository
     * @return Response
     */
    public function index(Request $request, SongRequestRepository $songRequestRepository, PaginationService $pagination): Response
    {
        $form = null;
        if ($this->isGranted('ROLE_USER')) {
            $songReq = new SongRequest();
            $user = $this->getUser();
            $songReq->setRequestedBy($user);
            $form = $this->createForm(SongRequestFormType::class, $songReq, ['attr' => ["class" => "form-horizontal"]]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($songReq);
                $em->flush();
                return $this->redirectToRoute("song_request_index");
            }
        }

        $qb = $songRequestRepository->createQueryBuilder('sr');
        $qb->select("sr");
        $qb->leftJoin("sr.requestedBy", 'u');
        $qb->where('sr.state IN (:displayable)')
            ->setParameter('displayable', [
                SongRequest::STATE_ASKED,
                SongRequest::STATE_IN_PROGRESS
            ]);
        switch($request->get('order')){
            case 1:
                $qb->addSelect('COUNT(v.id) AS HIDDEN count_votes');
                $qb->leftJoin('sr.songRequestVotes','v');
                $qb->groupBy("sr.id");
                $qb->orderBy("count_votes", "DESC")
                    ->addOrderBy("IF(u.isPatreon = true,1,0)", "DESC")
                    ->addOrderBy("sr.createdAt", 'DESC');
                break;
            default:
                $qb->orderBy("IF(u.isPatreon = true,1,0)", "DESC")
                    ->addOrderBy("sr.createdAt", 'DESC');
        }


        $songRequests = $pagination->setDefaults(50)->process($qb,$request);


        return $this->render('song_request/index.html.twig', [
            'songRequests' => $songRequests,
            'form' => $form ? $form->createView() : null
        ]);
    }

    /**
     * @Route("/claim/{id}", name="_claim")
     * @param Request $request
     * @param SongRequest $songRequest
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function claim(Request $request, SongRequest $songRequest, TranslatorInterface $translator): Response
    {
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account to access this page."));
            return $this->redirectToRoute('home');
        }
        /** @var Utilisateur $user */
        $user = $this->getUser();
        //not a mapper
        if (!$user->getIsMapper()) {
            $this->addFlash('danger', $translator->trans("You need to be a mapper to access this page."));
            return $this->redirectToRoute('user');
        }
        //song already get a mapper
        if ($songRequest->getMapper()) {
            $this->addFlash('danger', $translator->trans("This request is already taken by a mapper."));
            return $this->redirectToRoute('song_request_index');
        }
        //mapper already is mapping something
        if ($user->getSongRequestInProgress() !== null) {
            $this->addFlash('danger', $translator->trans("You already claimed a request, one thing at a time please."));
            return $this->redirectToRoute('song_request_index');
        }

        $songRequest->addMapperOnIt($user);
        $songRequest->setState(SongRequest::STATE_IN_PROGRESS);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', "Song request claimed !");
        return $this->redirectToRoute('song_request_index');
    }

    /**
     * @Route("/unclaim/{id}", name="_unclaim")
     * @param Request $request
     * @param SongRequest $songRequest
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function unclaim(Request $request, SongRequest $songRequest, TranslatorInterface $translator): Response
    {
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account to access this page."));
            return $this->redirectToRoute('home');
        }
        /** @var Utilisateur $user */
        $user = $this->getUser();
        //not a mapper
        if (!$user->getIsMapper()) {
            $this->addFlash('danger', $translator->trans("You need to be a mapper to access this page."));
            return $this->redirectToRoute('user');
        }


        if ($songRequest->getMapper() !== $user) {
            $this->addFlash('danger', $translator->trans("You are not the mapper that claim this request."));
            return $this->redirectToRoute('song_request_index');
        }
        $songRequest->removeMapperOnIt($user);
        $songRequest->setState(SongRequest::STATE_ASKED);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', "Song request unclaimed !");
        return $this->redirectToRoute('song_request_index');
    }

    /**
     * @Route("/toggle/one/{id}", name="_toggle")
     * @param Request $request
     * @param SongRequest $songRequest
     * @param SongRequestVoteRepository $songRequestVoteRepository
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function toggleOne(Request $request, SongRequest $songRequest, SongRequestVoteRepository $songRequestVoteRepository,TranslatorInterface $translator): Response
    {
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account to access this page."));
            return $this->redirectToRoute('home');
        }

        $vote = $songRequestVoteRepository->findOneBy([
            'user' => $this->getUser(),
            'songRequest' => $songRequest
        ]);
        $em = $this->getDoctrine()->getManager();

        if ($vote == null) {
            $vote = new SongRequestVote();
            $vote->setSongRequest($songRequest);
            $vote->setUser($this->getUser());
            $em->persist($vote);
        }else{
            $em->remove($vote);
        }
        $em->flush();

        return $this->redirectToRoute('song_request_index');
    }
}
