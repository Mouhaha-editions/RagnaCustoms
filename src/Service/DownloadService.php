<?php

namespace App\Service;

use App\Entity\DownloadCounter;
use App\Entity\Song;
use App\Entity\Utilisateur;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class DownloadService
{
    public function __construct(private Security $security, private EntityManagerInterface $em)
    {
    }

    public function alreadyDownloaded(Song $song): bool
    {
        /** @var Utilisateur $user */
        $user = $this->security->getUser();
        if ($user != null && $this->security->isGranted('ROLE_USER')) {
            $dl = $user->getDownloadCounters()->filter(function (DownloadCounter $downloadCounter) use ($song) {
                return $downloadCounter->getSong() === $song;
            });

            return $dl->count() >= 1;
        }

        return false;
    }

    public function addOne(Song $song, $apiKey = null): void
    {
        if ($apiKey == null) {
            if (!$this->security->isGranted('ROLE_USER')) {
                return;
            }
            /** @var Utilisateur $user */
            $user = $this->security->getUser();
        } else {
            $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['apiKey' => $apiKey]);
        }
        if ($user == null) {
            return;
        }
        $dlu = $this->em->getRepository(DownloadCounter::class)->createQueryBuilder('dc')->where(
            'dc.song = :song'
        )->andWhere('(dc.user = :user)')->setParameter('user', $user)->setParameter('song', $song)->setFirstResult(
            0
        )->setMaxResults(1)->getQuery()->getOneOrNullResult();
        if ($dlu == null) {
            $dlu = new DownloadCounter();
            $dlu->setSong($song);
            $this->em->persist($dlu);
        }
        $dlu->setUser($user);
        $dlu->setUpdatedAt(new DateTime());
        $this->em->flush();
    }
}

