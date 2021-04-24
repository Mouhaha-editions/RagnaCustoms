<?php

namespace App\Service;

use App\Entity\Score;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use App\Repository\ScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use ZipArchive;

class ScoreService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getMine(Utilisateur $user, SongDifficulty $songDifficulty)
    {
        $return = [];
        /** @var Score $score */
        $score = $this->em->getRepository(Score::class)->createQueryBuilder("s")
            ->where('s.user = :user')
            ->andWhere('s.songDifficulty = :songDifficulty')
            ->setParameter('user', $user)
            ->setParameter('songDifficulty', $songDifficulty)
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
        if ($score == null) {
            return null;
        }
        $return['score'] = $score;

        $otherScore = $this->em->getRepository(Score::class)->createQueryBuilder("s")
            ->where('s.songDifficulty = :songDifficulty')
            ->andWhere("s.score >= :score")
            ->andWhere("s.user != :user")
            ->setParameter('score', $score->getScore())
            ->setParameter('user', $user)
            ->setParameter('songDifficulty', $songDifficulty)
            ->getQuery()->getResult();
        $return['place'] = count($otherScore)+1;
        return $return;
    }
}

