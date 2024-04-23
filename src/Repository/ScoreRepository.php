<?php

namespace App\Repository;

use App\Controller\WanadevApiController;
use App\Entity\Score;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Score|null find($id, $lockMode = null, $lockVersion = null)
 * @method Score|null findOneBy(array $criteria, array $orderBy = null)
 * @method Score[]    findAll()
 * @method Score[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Score::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Score $entity, bool $flush = true): void
    {
        if (!$entity->getId()) {
            $this->_em->persist($entity);
        }

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Score $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function getOneByUserDiffVrOrNot(
        Utilisateur $user,
        SongDifficulty $songDiff,
        bool $isVR,
        bool $isOkodo = false
    ): ?Score {
        $qb = $this
            ->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->andWhere('s.songDifficulty = :songDifficulty')
            ->setParameter('songDifficulty', $songDiff);

        if ($isVR) {
            $qb->andWhere('s.plateform IN (:plateformVr)')
                ->setParameter('plateformVr', WanadevApiController::VR_PLATEFORM);
        } else {
            if ($isOkodo) {
                $qb->andWhere('s.plateform IN (:plateformVr)')
                    ->setParameter('plateformVr', WanadevApiController::OKOD_PLATEFORM);
            } else {
                $qb->andWhere('s.plateform IN (:plateformVr)')
                    ->setParameter('plateformVr', WanadevApiController::FLAT_PLATEFORM);
            }
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
