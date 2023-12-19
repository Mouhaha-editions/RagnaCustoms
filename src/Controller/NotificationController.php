<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use App\Enum\EEmail;
use App\Enum\ENotification;
use App\Repository\NotificationRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationController extends AbstractController
{
    #[Route('/notification', name: 'app_notification')]
    public function index(
        Request $request,
        TranslatorInterface $translator,
        PaginationService $paginationService,
        NotificationRepository $notificationRepository
    ): Response {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account!"));

            return $this->redirectToRoute('home');
        }

        $qb = $notificationRepository->createQueryBuilder('n')->where('n.user = :user')->setParameter(
            'user',
            $this->getUser()
        )->orderBy('n.createdAt', 'DESC');

        $notifications = $paginationService->process($qb, $request);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notifications/setting', name: 'notifications_setting')]
    public function notificationSetting(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $doctrine,
        PaginationService $paginationService,
        NotificationRepository $notificationRepository
    ): Response {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account!"));

            return $this->redirectToRoute('home');
        }
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $data = [
            "emailPreference" => $user->getEmailPreferences(),
            "notificationPreference" => $user->getNotificationPreferences(),
        ];

        $form = $this->createFormBuilder($data)
            ->add('emailPreference', EnumType::class, [
                'class' => EEmail::class,
                "choice_label" => "label",
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('notificationPreference', EnumType::class, [
                'class' => ENotification::class,
                "choice_label" => "label",
                'multiple' => true,
                'expanded' => true,
            ]);

        $form = $form->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setEmailPreference(serialize($form->get('emailPreference')->getData()));
            $user->setNotificationPreference(serialize($form->get('notificationPreference')->getData()));
            $doctrine->getManager()->flush();
            $this->addFlash('success', "Your preferences are saved!");
        }

        return $this->render('notification/preference.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/notification/toggle/read/{id}', name: 'notification_toggle_read')]
    public function notification_toggle_read(
        Notification $notification,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator
    ) {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account!"),
                "result" => $this->renderView('notification/partial/buttons.html.twig', [
                    "notification" => $notification,
                ]),
            ]);
        }

        if ($this->getUser() !== $notification->getUser()) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You are not the owners!"),
                "result" => $this->renderView('notification/partial/buttons.html.twig', [
                    "notification" => $notification,
                ]),
            ]);
        }

        $notification->setState(
            $notification->getState(
            ) == Notification::STATE_UNREAD ? Notification::STATE_READ : Notification::STATE_UNREAD
        );
        $doctrine->getManager()->flush();

        return new JsonResponse([
            "error" => false,
            "errorMessage" => "",
            "result" => $this->renderView('notification/partial/buttons.html.twig', [
                "notification" => $notification,
            ]),
        ]);
    }

    #[Route('/notification/toggle/read-all', name: 'notification_read_all')]
    public function notification_readAll(
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        NotificationRepository $notificationRepository
    ) {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account!"));

            return $this->redirectToRoute('app_notification');
        }

        foreach (
            $notificationRepository->findBy([
                'user' => $this->getUser(),
                'state' => Notification::STATE_UNREAD,
            ]) as $notification
        ) {
            $notification->setState(Notification::STATE_READ);
        }

        $doctrine->getManager()->flush();

        return $this->redirectToRoute('app_notification');
    }

    #[Route('/notification/toggle/delete/{id}', name: 'notification_delete')]
    public function notification_delete(
        Notification $notification,
        TranslatorInterface $translator,
        NotificationRepository $notificationRepository
    ) {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account!"));

            return $this->redirectToRoute('app_notification');
        }

        if ($this->getUser() !== $notification->getUser()) {
            $this->addFlash('danger', $translator->trans("You are not the owners!"));

            return $this->redirectToRoute('app_notification');
        }

        $notificationRepository->remove($notification, true);

        return $this->redirectToRoute('app_notification');
    }
}
