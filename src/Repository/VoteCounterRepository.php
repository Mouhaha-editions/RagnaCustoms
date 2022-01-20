<?php

namespace App\Repository;

use App\Entity\VoteCounter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VoteCounter|null find($id, $lockMode = null, $lockVersion = null)
 * @method VoteCounter|null findOneBy(array $criteria, array $orderBy = null)
 * @method VoteCounter[]    findAll()
 * @method VoteCounter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VoteCounterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VoteCounter::class);
    }    
}
