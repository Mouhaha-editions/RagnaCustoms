<?php

namespace App\Service;

use App\Entity\Playlist;
use App\Entity\Song;
use App\Entity\Vote;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class MapperService
{
    public function __construct(protected EntityManagerInterface $em)
    {
    }

    public function getTotalDownloads(UserInterface $user)
    {
        $res = $this->em->getRepository(Song::class)->createQueryBuilder('s')
            ->select("SUM(s.downloads)")
            ->leftJoin('s.mappers','m')
            ->where('m.id = :user')
            ->andWhere('s.isDeleted != 1')
            ->setParameter('user', $user)
            ->groupBy("m.id")
            ->getQuery()->getOneOrNullResult();
        return $res != null ? array_pop($res):0;
    }

    public function getTotalUpperVotes(UserInterface $user)
    {
        $res = $this->em->getRepository(Song::class)->createQueryBuilder('s')
            ->select("SUM(s.voteUp)")
            ->leftJoin('s.mappers','m')
            ->where('m.id = :user')
            ->andWhere('s.isDeleted != 1')
            ->setParameter('user', $user)
            ->groupBy("m.id")
            ->getQuery()->getOneOrNullResult();
        return $res != null ? array_pop($res):0;
    }

    public function getTotalLowerVotes(UserInterface $user)
    {
        $res = $this->em->getRepository(Song::class)->createQueryBuilder('s')
            ->select("SUM(s.voteDown)")
            ->leftJoin('s.mappers','m')
            ->where('m.id = :user')
            ->andWhere('s.isDeleted != 1')
            ->setParameter('user', $user)
            ->groupBy("m.id")
            ->getQuery()->getOneOrNullResult();
        return $res != null ? array_pop($res):0;
    }

    public function getTotalReview(UserInterface $user)
    {
        $res = $this->em->getRepository(Song::class)->createQueryBuilder('s')
            ->select("SUM(s.countVotes)")
            ->leftJoin('s.mappers','m')
            ->where('m.id = :user')
            ->andWhere('s.isDeleted != 1')
            ->andWhere('s.wip != 1')
            ->andWhere('s.countVotes != 0')
            ->setParameter('user', $user)
            ->groupBy("m.id")
            ->getQuery()->getOneOrNullResult();
        return $res != null ? array_pop($res):0;
    }

    public function getAvgFunFactor(UserInterface $user)
    {
        $res = $this->em->getRepository(Vote::class)->createQueryBuilder('v')
            ->join('v.song','s')
            ->select("SUM(v.funFactor)/COUNT(v.funFactor)")
            ->leftJoin('s.mappers','m')
            ->where('m.id = :user')
            ->andWhere('s.isDeleted != 1')
            ->andWhere('s.countVotes != 0')
            ->setParameter('user', $user)
            ->groupBy("m.id")
            ->getQuery()->getOneOrNullResult();
        return $res != null ? array_pop($res):0;
    }

    public function getAvgRhythm(UserInterface $user)
    {
        $res = $this->em->getRepository(Vote::class)->createQueryBuilder('v')
            ->join('v.song','s')
            ->select("SUM(v.rhythm)/COUNT(v.rhythm)")
            ->leftJoin('s.mappers','m')
            ->where('m.id = :user')
            ->andWhere('s.isDeleted != 1')
            ->andWhere('s.countVotes != 0')
            ->setParameter('user', $user)
            ->groupBy("m.id")
            ->getQuery()->getOneOrNullResult();
        return $res != null ? array_pop($res):0;
    }
    public function getAvgPatternQuality(UserInterface $user)
    {
        $res = $this->em->getRepository(Vote::class)->createQueryBuilder('v')
            ->join('v.song','s')
            ->select("SUM(v.patternQuality)/COUNT(v.patternQuality)")
            ->leftJoin('s.mappers','m')
            ->where('m.id = :user')
            ->andWhere('s.isDeleted != 1')
            ->andWhere('s.countVotes != 0')
            ->setParameter('user', $user)
            ->groupBy("m.id")
            ->getQuery()->getOneOrNullResult();
        return $res != null ? array_pop($res):0;
    }
    public function getAvgReadability(UserInterface $user)
    {
        $res = $this->em->getRepository(Vote::class)->createQueryBuilder('v')
            ->join('v.song','s')
            ->select("SUM(v.readability)/COUNT(v.readability)")
            ->leftJoin('s.mappers','m')
            ->where('m.id = :user')
            ->andWhere('s.isDeleted != 1')
            ->andWhere('s.countVotes != 0')
            ->setParameter('user', $user)
            ->groupBy("m.id")
            ->getQuery()->getOneOrNullResult();
        return $res != null ? array_pop($res):0;
    }

    public function getPlaylistShowcase(UserInterface $user)
    {
        return $this->em->getRepository(Playlist::class)
            ->findBy(['isFeatured'=>true, 'user'=> $user],['updatedAt'=>'DESC']);
    }
}

