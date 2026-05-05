<?php

namespace App\Service;

use App\Entity\Trip;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Repository\TripRepository;

class TripService
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly TripRepository $tripRepository,
    ) {
    }

    /**
     * @return array{canBook: bool, reason: string|null}
     */
    public function canUserBook(Trip $trip, ?User $user): array
    {
        if (!$user instanceof User) {
            return ['canBook' => false, 'reason' => 'not_logged_in'];
        }

        if (!\in_array('ROLE_PASSAGER', $user->getRoles(), true)) {
            return ['canBook' => false, 'reason' => 'not_passenger'];
        }

        if ($user->getId() === $trip->getDriver()?->getId()) {
            return ['canBook' => false, 'reason' => 'is_driver'];
        }

        $departure = $trip->getDepartureDateTime();
        if (null !== $departure && $departure < new \DateTimeImmutable()) {
            return ['canBook' => false, 'reason' => 'trip_past'];
        }

        if (!$trip->isActive()) {
            return ['canBook' => false, 'reason' => 'trip_past'];
        }

        if (($trip->getSeatsAvailable() ?? 0) < 1) {
            return ['canBook' => false, 'reason' => 'no_seats'];
        }

        if (null !== $this->reservationRepository->findBlockingReservationForPassenger($trip, $user)) {
            return ['canBook' => false, 'reason' => 'already_booked'];
        }

        return ['canBook' => true, 'reason' => 'ok'];
    }

    public function formatTripDuration(Trip $trip): string
    {
        $dep = $trip->getDepartureDateTime();
        if (null === $dep) {
            return '';
        }

        $now = new \DateTimeImmutable();
        $delta = $dep->getTimestamp() - $now->getTimestamp();

        if ($delta > 0) {
            return $this->formatFutureInterval($delta);
        }

        return $this->formatPastInterval(-$delta);
    }

    /**
     * @return list<Trip>
     */
    public function getSimilarTrips(Trip $trip, int $limit = 3): array
    {
        return $this->tripRepository->findSimilarByDestination($trip, $limit);
    }

    private function formatFutureInterval(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        if ($days >= 1) {
            return sprintf('Départ dans %d jour%s', $days, $days > 1 ? 's' : '');
        }

        $hours = intdiv($seconds, 3600);
        if ($hours >= 1) {
            return sprintf('Départ dans %d heure%s', $hours, $hours > 1 ? 's' : '');
        }

        $minutes = max(1, intdiv($seconds, 60));

        return sprintf('Départ dans %d minute%s', $minutes, $minutes > 1 ? 's' : '');
    }

    private function formatPastInterval(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        if ($days >= 1) {
            return sprintf('Il y a %d jour%s', $days, $days > 1 ? 's' : '');
        }

        $hours = intdiv($seconds, 3600);
        if ($hours >= 1) {
            return sprintf('Il y a %d heure%s', $hours, $hours > 1 ? 's' : '');
        }

        $minutes = max(1, intdiv($seconds, 60));

        return sprintf('Il y a %d minute%s', $minutes, $minutes > 1 ? 's' : '');
    }
}
