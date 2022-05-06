<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationController extends AbstractController
{
    #[Route('/notification', name: 'app_notification')]
    public function index(Request $request, TranslatorInterface $translator, PaginationService $paginationService, NotificationRepository $notificationRepository): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account to access this page."));
            return $this->redirectToRoute('home');
        }
        $qb = $notificationRepository->createQueryBuilder('n')->where('n.user = :user')->setParameter('user', $this->getUser())->orderBy('n.createdAt', 'DESC');

        $notifications = $paginationService->process($qb, $request);
        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notification/toggle/read/{id}', name: 'notification_toggle_read')]
    public function notification_toggle_read(Notification $notification, ManagerRegistry $doctrine, TranslatorInterface $translator)
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account to follow!"),
                "result" => $this->renderView('notification/partial/buttons.html.twig', [
                    "notification" => $notification
                ])
            ]);
        }
        if ($this->getUser() !== $notification->getUser()) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You are not the owners!"),
                "result" => $this->renderView('notification/partial/buttons.html.twig', [
                    "notification" => $notification
                ])
            ]);
        }

        $notification->setState($notification->getState() == Notification::STATE_UNREAD ? Notification::STATE_READ : Notification::STATE_UNREAD);
        $doctrine->getManager()->flush();
        return new JsonResponse([
            "error" => false,
            "errorMessage" => "",
            "result" => $this->renderView('notification/partial/buttons.html.twig', [
                "notification" => $notification
            ])
        ]);
    }
}
