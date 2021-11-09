<?php

namespace App\Controller;

use App\Entity\SongRequest;
use App\Form\SongRequestFormType;
use App\Repository\SongRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    public function index(Request $request, SongRequestRepository $songRequestRepository): Response
    {
        $form = null;
        if ($this->isGranted('ROLE_USER')) {
            $songReq = new SongRequest();
            $user = $this->getUser();
            $songReq->setRequestedBy($user);
            $form = $this->createForm(SongRequestFormType::class, $songReq,['attr'=>["class"=>"form-horizontal"]]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($songReq);
                $em->flush();
                return $this->redirectToRoute("song_request_index");
            }
        }

        $qb = $songRequestRepository->createQueryBuilder('sr');
    $qb->leftJoin("sr.requestedBy",'u');
    $qb->orderBy("IF(u.isPatreon = true,1,0)","DESC")
    ->addOrderBy("sr.createdAt");

        $songRequests = $qb->getQuery()
            ->getResult();


        return $this->render('song_request/index.html.twig', [
            'songRequests' => $songRequests,
            'form' => $form ? $form->createView() : null
        ]);
    }
}
