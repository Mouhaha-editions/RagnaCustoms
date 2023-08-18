<?php

namespace App\Repository;

use App\Entity\Friend;
use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Friend>
 *
 * @method Friend|null find($id, $lockMode = null, $lockVersion = null)
 * @method Friend|null findOneBy(array $criteria, array $orderBy = null)
 * @method Friend[]    findAll()
 * @method Friend[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friend::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Friend $entity, bool $flush = true): void
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
    public function remove(Friend $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @return Friend[]
     */
    public function getMine(?UserInterface $user): array
    {
        $qb = $this->createQueryBuilder('f')
        ->leftJoin('f.user', 'user')
        ->leftJoin('f.friend', 'friend')
        ->andWhere('f.state = :accepted')
        ->setParameter('accepted', Friend::STATE_ACCEPTED);
        $qb
            ->andWhere($qb->expr()->orX('user.id = :user', 'friend.id  = :user'))
            ->setParameter('user', $user)
            ->orderBy('IF(user.id = :user, friend.username, user.username)', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
