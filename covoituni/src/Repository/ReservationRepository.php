<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Trip;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findBlockingReservationForPassenger(Trip $trip, User $user): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.trip = :trip')
            ->andWhere('r.passenger = :user')
            ->andWhere('r.status != :cancelled')
            ->setParameter('trip', $trip)
            ->setParameter('user', $user)
            ->setParameter('cancelled', 'cancelled')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Reservation>
     */
    public function findByTripOrdered(Trip $trip): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.trip = :trip')
            ->setParameter('trip', $trip)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Reservation>
     */
    public function findForPassengerOrdered(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.trip', 't')->addSelect('t')
            ->innerJoin('t.driver', 'd')->addSelect('d')
            ->andWhere('r.passenger = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
