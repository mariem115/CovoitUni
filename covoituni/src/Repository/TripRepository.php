<?php

namespace App\Repository;

use App\Entity\Trip;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Trip>
 */
class TripRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trip::class);
    }

    /**
     * @return list<Trip>
     */
    public function findRecentActive(int $limit = 6): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.departureDateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Trip>
     */
    public function findActiveTrips(): array
    {
        return $this->createActiveTripsQueryBuilder()
            ->getQuery()
            ->getResult();
    }

    public function createActiveTripsQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.departureDateTime', 'ASC');
    }

    /**
     * @return list<Trip>
     */
    public function searchTrips(string $departure, string $destination, ?\DateTimeImmutable $date, int $minSeats = 1): array
    {
        return $this->createSearchTripsQueryBuilder($departure, $destination, $date, $minSeats)
            ->getQuery()
            ->getResult();
    }

    public function createSearchTripsQueryBuilder(string $departure, string $destination, ?\DateTimeImmutable $date, int $minSeats = 1): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->andWhere('t.seatsAvailable >= :minSeats')
            ->setParameter('minSeats', $minSeats)
            ->orderBy('t.departureDateTime', 'ASC');

        $departure = trim($departure);
        if ($departure !== '') {
            $qb->andWhere('t.departure LIKE :departure')
                ->setParameter('departure', '%'.$departure.'%');
        }

        $destination = trim($destination);
        if ($destination !== '') {
            $qb->andWhere('t.destination LIKE :destination')
                ->setParameter('destination', '%'.$destination.'%');
        }

        if ($date !== null) {
            $start = $date->setTime(0, 0, 0);
            $end = $start->modify('+1 day');
            $qb->andWhere('t.departureDateTime >= :dayStart AND t.departureDateTime < :dayEnd')
                ->setParameter('dayStart', $start)
                ->setParameter('dayEnd', $end);
        }

        return $qb;
    }

    /**
     * @return list<Trip>
     */
    public function findByDriver(User $driver): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.driver = :driver')
            ->setParameter('driver', $driver)
            ->orderBy('t.departureDateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveTrips(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Trip>
     */
    public function findSimilarActiveTrips(Trip $trip, int $limit = 4): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->andWhere('t.id != :id')
            ->setParameter('id', $trip->getId())
            ->andWhere('t.destination = :dest OR t.departure = :dep')
            ->setParameter('dest', $trip->getDestination())
            ->setParameter('dep', $trip->getDeparture())
            ->orderBy('t.departureDateTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
