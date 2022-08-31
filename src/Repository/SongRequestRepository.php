<?php

namespace App\Repository;

use App\Entity\SongRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SongRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method SongRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method SongRequest[]    findAll()
 * @method SongRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SongRequest::class);
    }
    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(SongRequest $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(SongRequest $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
