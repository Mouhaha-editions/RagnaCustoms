<?php

namespace App\Controller;

use App\Entity\FollowMapper;
use App\Entity\Utilisateur;
use App\Repository\FollowMapperRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class FollowerController extends AbstractController
{
    #[Route('/follower/toggle/{id}/{type}', name: 'follow_toggle')]
    public function followToggle(Utilisateur $mapper, int $type,ManagerRegistry $doctrine, FollowMapperRepository $followMapperRepository, TranslatorInterface $translator): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account!"),
                "result" => $this->renderView(($type == 1?'follower/partial/buttons.html.twig':'follower/partial/bigButtons.html.twig'), [
                    "mapper" => $mapper
                ])
            ]);
        }
        if ($this->getUser() === $mapper) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You can't follow yourself!"),
                "result" => $this->renderView(($type == 1?'follower/partial/buttons.html.twig':'follower/partial/bigButtons.html.twig'), [
                    "mapper" => $mapper
                ])
            ]);
        }
        $em = $doctrine->getManager();
        $follow = $followMapperRepository->findOneBy([
            'mapper' => $mapper,
            "user" => $this->getUser()
        ]);
        if ($follow == null) {
            $follow = new FollowMapper();
            $follow->setUser($this->getUser());
            $follow->setMapper($mapper);
            $follow->setIsNotificationEnabled(true);
            $em->persist($follow);
        }else{
            $em->remove($follow);
        }
        $em->flush();

        return new JsonResponse([
            "error" => false,
            "errorMessage" => "",
            "result" => $this->renderView(($type == 1?'follower/partial/buttons.html.twig':'follower/partial/bigButtons.html.twig'), [
                "mapper" => $mapper
            ])
        ]);
    }

    #[Route('/follower/notification/toggle/{id}/{type}', name: 'follow_notification_toggle')]
    public function followNotificationToggle(Utilisateur $mapper,int $type, ManagerRegistry $doctrine, FollowMapperRepository $followMapperRepository, TranslatorInterface $translator): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account!"),
                "result" => $this->renderView(($type == 1?'follower/partial/buttons.html.twig':'follower/partial/bigButtons.html.twig'), [
                    "mapper" => $mapper
                ])
            ]);
        }
        $em = $doctrine->getManager();
        $follow = $followMapperRepository->findOneBy([
            'mapper' => $mapper,
            "user" => $this->getUser()
        ]);
        $follow->setIsNotificationEnabled(!$follow->getIsNotificationEnabled());
        $em->flush();

        return new JsonResponse([
            "error" => false,
            "errorMessage" => "",
            "result" => $this->renderView(($type == 1?'follower/partial/buttons.html.twig':'follower/partial/bigButtons.html.twig'), [
                "mapper" => $mapper
            ])
        ]);
    }
}
