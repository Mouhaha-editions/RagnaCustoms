<?php

namespace App\Service;

use App\Entity\Challenge;
use App\Entity\Participation;
use App\Entity\User;
use App\Repository\ChallengeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class StatistiquesService
{
    /** @var EntityManagerInterface */
    private EntityManagerInterface $em;
    /**
     * @var ChallengeRepository
     */
    private ChallengeRepository $challengeRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function countChallenges()
    {
        return $this->em->getRepository(Challenge::class)->count(['user' => null]);
    }

    public function countParticipations()
    {
        return $this->em->getRepository(Participation::class)->count(["enabled" => true]);
    }

    public function countParticipationsUniques()
    {
        $rep = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(DISTINCT(u.id)) as count')
            ->join('u.participations', 'p')
            ->where("p.enabled = true")
            ->distinct()
            ->getQuery()
            ->getOneOrNullResult();
        return $rep["count"];
    }

    public function countTwitchers()
    {
        return count($this->em->getRepository(User::class)->createQueryBuilder('u')->where("u.twitchID is not null")
            ->getQuery()->getResult());

    }

    /**
     * @return Challenge[]|ArrayCollection
     */
    public function getChallenges()
    {
        return $this->em->getRepository(Challenge::class)->findBy([
            'user' => null,
            'display' => true
        ], ['registrationOpening' => "ASC"]);
    }
}