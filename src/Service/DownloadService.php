<?php

namespace App\Service;

use App\Entity\DownloadCounter;
use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Entity\Vote;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class DownloadService
{
    private $security;
    protected $requestStack;
    protected $em;

    public function __construct(Security $security, RequestStack $requestStack, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    public function alreadyDownloaded(Song $song): bool
    {
        /** @var Utilisateur $user */
        $user = $this->security->getUser();
        if ($user != null && $this->security->isGranted('ROLE_USER')) {
            foreach ($user->getDownloadCounters() as $downloadCounter) {
                if ($downloadCounter->getSong() === $song && $song->getLastDateUpload() < $downloadCounter->getUpdatedAt()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function addOne(Song $song)
    {
        if(!$this->security->isGranted('ROLE_USER')){return;}
        /** @var Utilisateur $user */
        $user = $this->security->getUser();

        $dlu = $this->em->getRepository(DownloadCounter::class)->createQueryBuilder('dc')
            ->where('dc.song = :song')
            ->andWhere('(dc.user = :user)')
            ->setParameter('user', $user)
            ->setParameter('song', $song)
            ->setFirstResult(0)->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
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

