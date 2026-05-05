<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\RatingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RatingController extends AbstractController
{
    #[Route('/noter/{reservationId}', name: 'app_rating_new', requirements: ['reservationId' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PASSAGER')]
    public function rate(
        Request $request,
        #[MapEntity(id: 'reservationId')]
        Reservation $reservation,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $this->denyAccessUnlessGranted('ROLE_PASSAGER');

        $reason = $this->getRatingBlockReason($reservation, $user);
        if (null !== $reason) {
            $this->addFlash('warning', $reason);

            return $this->redirectToRoute('app_reservation_my');
        }

        $rating = new Rating();
        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trip = $reservation->getTrip();
            $driver = $trip?->getDriver();
            if (null === $driver) {
                $this->addFlash('danger', 'Conducteur introuvable.');

                return $this->redirectToRoute('app_reservation_my');
            }

            $rating->setReviewer($user);
            $rating->setDriver($driver);
            $rating->setReservation($reservation);
            $reservation->setIsRated(true);

            $entityManager->persist($rating);
            $entityManager->flush();

            $this->addFlash('success', 'Merci pour votre avis !');

            return $this->redirectToRoute('app_reservation_my');
        }

        return $this->render('rating/new.html.twig', [
            'form' => $form,
            'reservation' => $reservation,
        ]);
    }

    private function getRatingBlockReason(Reservation $reservation, User $user): ?string
    {
        $passenger = $reservation->getPassenger();
        if (null === $passenger || (int) $user->getId() !== (int) $passenger->getId()) {
            return 'Vous ne pouvez pas noter cette réservation.';
        }

        if ($reservation->isPassengerRatingFormOpen()) {
            return null;
        }

        if ('confirmed' !== $reservation->getStatus()) {
            return 'Seules les réservations confirmées peuvent être notées.';
        }

        if ($reservation->isRated() || null !== $reservation->getRating()) {
            return null !== $reservation->getRating()
                ? 'Un avis existe déjà pour cette réservation.'
                : 'Ce trajet a déjà été noté.';
        }

        return 'Vous pourrez noter le conducteur après la date du trajet.';
    }
}
