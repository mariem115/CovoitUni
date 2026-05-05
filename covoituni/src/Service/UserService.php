<?php

namespace App\Service;

use App\Entity\User;

class UserService
{
    public function getAverageRating(User $user): float
    {
        $received = $user->getReceivedRatings();
        if ($received->isEmpty()) {
            return 0.0;
        }

        $sum = 0;
        foreach ($received as $rating) {
            $sum += $rating->getScore() ?? 0;
        }

        return round($sum / $received->count(), 1);
    }

    /**
     * HTML snippet with Font Awesome stars (filled / half / empty).
     */
    public function getStarDisplay(float $rating): string
    {
        $rating = max(0.0, min(5.0, $rating));
        $scaled = (int) round($rating * 2);
        $full = intdiv($scaled, 2);
        $half = $scaled % 2;
        $empty = 5 - $full - $half;

        $html = '<span class="cu-star-display d-inline-flex align-items-center gap-0 text-warning" role="img" aria-label="'.htmlspecialchars(sprintf('Note %.1f sur 5', $rating), ENT_QUOTES, 'UTF-8').'">';
        for ($i = 0; $i < $full; ++$i) {
            $html .= '<i class="fa-solid fa-star" aria-hidden="true"></i>';
        }
        if (1 === $half) {
            $html .= '<i class="fa-solid fa-star-half-stroke" aria-hidden="true"></i>';
        }
        for ($i = 0; $i < $empty; ++$i) {
            $html .= '<i class="fa-regular fa-star" aria-hidden="true"></i>';
        }
        $html .= '</span>';

        return $html;
    }

    /**
     * @return array{asDriver: int, asPassenger: int, avgRating: float}
     */
    public function getTripStats(User $user): array
    {
        return [
            'asDriver' => $user->getTripsAsDriver()->count(),
            'asPassenger' => $user->getReservations()->count(),
            'avgRating' => $this->getAverageRating($user),
        ];
    }
}
