<?php

namespace App\Controller;

use App\Entity\CustomEvent;
use App\Form\CustomEventType;
use App\Repository\CustomEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/custom/event')]
class CustomEventController extends AbstractController
{
    #[Route('/', name: 'app_custom_event_index', methods: ['GET'])]
    public function index(CustomEventRepository $customEventRepository): Response
    {
        return $this->render('custom_event/index.html.twig', [
            'custom_events' => $customEventRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_custom_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $customEvent = new CustomEvent();
        $form = $this->createForm(CustomEventType::class, $customEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($customEvent);
            $entityManager->flush();

            return $this->redirectToRoute('app_custom_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('custom_event/new.html.twig', [
            'custom_event' => $customEvent,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_custom_event_show', methods: ['GET'])]
    public function show(CustomEvent $customEvent): Response
    {
        return $this->render('custom_event/show.html.twig', [
            'custom_event' => $customEvent,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_custom_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CustomEvent $customEvent, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CustomEventType::class, $customEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_custom_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('custom_event/edit.html.twig', [
            'custom_event' => $customEvent,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_custom_event_delete', methods: ['POST'])]
    public function delete(Request $request, CustomEvent $customEvent, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$customEvent->getId(), $request->request->get('_token'))) {
            $entityManager->remove($customEvent);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_custom_event_index', [], Response::HTTP_SEE_OTHER);
    }
}
