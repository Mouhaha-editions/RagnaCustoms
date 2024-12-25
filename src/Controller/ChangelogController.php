<?php

namespace App\Controller;

use App\Entity\Changelog;
use App\Entity\Utilisateur;
use App\Repository\ChangelogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChangelogController extends AbstractController
{
    #[Route('/changelog/unread', name: 'changelog_unread', methods: ['GET'])]
    public function getUnreadChangelog(ChangelogRepository $changelogRepository): JsonResponse
    {
        $user = $this->getUser();
        if ($this->isGranted('ROLE_MODERATOR')) {
            $changelogs = $changelogRepository->findNoReadOrWip($user);
        }else{
            $changelogs = $changelogRepository->findNoRead($user);
        }


        return $this->json($changelogs);
    }

    #[Route('/changelog/mark-as-read/{id}', name: 'changelog_mark_as_read', methods: ['POST'])]
    public function markAsRead(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var ?Utilisateur $user */
        $user = $this->getUser();
        $changelog = $em->getRepository(Changelog::class)->find($id);

        if (!$changelog) {
            return $this->json(['error' => 'Changelog not found'], 404);
        }

        if ($user) {
            $user->addChangelog($changelog);

            $em->persist($user);
            $em->flush();
        }

        return $this->json(['success' => true]);
    }
}
