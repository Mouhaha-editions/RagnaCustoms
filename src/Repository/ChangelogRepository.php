<?php

namespace App\Repository;

use App\Entity\Changelog;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Changelog>
 */
class ChangelogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Changelog::class);
    }

    //    /**
    //     * @return Changelog[] Returns an array of Changelog objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Changelog
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findNoRead(?UserInterface $user): array
    {
        if ($user) {
            return $this->createQueryBuilder('c')
                ->andWhere('c.id NOT IN (:changes)')
                ->andWhere('c.isWip = false')
                ->setParameter('changes', $user->getChangelogs()->count() != 0 ? $user->getChangelogs() : [0])
                ->orderBy('c.createdAt', 'ASC')
                ->getQuery()->getResult();
        }

        return [];
    }

    public function findNoReadOrWip(UserInterface $user): array
    {
        $qb = $this->createQueryBuilder('c');

        return $qb
            ->andWhere(
                $qb->expr()->orX(
                    'c.id NOT IN (:changes)',
                    'c.isWip = true',
                )
            )
            ->setParameter('changes', $user->getChangelogs()->count() != 0 ? $user->getChangelogs() : [0])
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()->getResult();

    }
}
