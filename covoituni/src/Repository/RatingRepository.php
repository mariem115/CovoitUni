<?php

namespace App\Repository;

use App\Entity\Rating;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    /**
     * @return list<Rating>
     */
    public function findReceivedForDriverOrdered(User $driver): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.reviewer', 'rev')->addSelect('rev')
            ->andWhere('r.driver = :d')
            ->setParameter('d', $driver)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
