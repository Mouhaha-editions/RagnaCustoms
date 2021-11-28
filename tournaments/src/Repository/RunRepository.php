<?php

namespace App\Repository;

use App\Entity\Challenge;
use App\Entity\Run;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Run|null find($id, $lockMode = null, $lockVersion = null)
 * @method Run|null findOneBy(array $criteria, array $orderBy = null)
 * @method Run[]    findAll()
 * @method Run[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Run::class);
    }

    /**
     * @param Challenge $challenge
     * @return Run[]|ArrayCollection
     */
    public function findByScore(Challenge $challenge)
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.challenge = :challenge')
            ->andWhere('r.training != true')
            ->setParameter('challenge', $challenge)
            ->orderBy('r.ComputedScore', 'Desc')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @param Challenge $challenge
     * @return Run[]|ArrayCollection
     */
    public function findByUserAndChallenge(User $user, Challenge $challenge)
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.challenge = :challenge')
            ->andWhere('r.user = :user')
            ->andWhere('r.training = false')
            ->setParameter('challenge', $challenge)
            ->setParameter('user', $user)
            ->orderBy('r.startDate', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }



}
