<?php


namespace App\Service;


use App\Entity\Challenge;
use Doctrine\ORM\EntityManagerInterface;

class ChallengeService
{
    /** @var EntityManagerInterface */
    private EntityManagerInterface $em;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @return Challenge|null
     */
    public function getRunningChallenge()
    {
        return $this->em->getRepository(Challenge::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.challengeDates','date')
            ->where("date.startDate <= :now")
            ->andWHere("date.endDate >= :now")
            ->setParameter('now', new \DateTime())
            ->getQuery()->getOneOrNullResult();
    }
}