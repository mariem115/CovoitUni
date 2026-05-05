<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Trip;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Security\TripVoter;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReservationController extends AbstractController
{
    #[Route('/reserver/{tripId}', name: 'app_reservation_book', requirements: ['tripId' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_PASSAGER')]
    public function book(
        Request $request,
        #[MapEntity(id: 'tripId')]
        Trip $trip,
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository,
        MailerService $mailerService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PASSAGER');

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('book_trip_'.$trip->getId(), $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->getId() === $trip->getDriver()?->getId()) {
            $this->addFlash('danger', 'Vous ne pouvez pas réserver votre propre trajet.');

            return $this->redirectToRoute('app_trajet_detail', ['id' => $trip->getId()]);
        }

        if (($trip->getSeatsAvailable() ?? 0) < 1) {
            $this->addFlash('danger', 'Ce trajet n\'a plus de places disponibles.');

            return $this->redirectToRoute('app_trajet_detail', ['id' => $trip->getId()]);
        }

        if (!$trip->isActive()) {
            $this->addFlash('danger', 'Ce trajet n\'accepte plus de réservations.');

            return $this->redirectToRoute('app_trajet_detail', ['id' => $trip->getId()]);
        }

        if (null !== $reservationRepository->findBlockingReservationForPassenger($trip, $user)) {
            $this->addFlash('danger', 'Vous avez déjà une réservation en cours pour ce trajet.');

            return $this->redirectToRoute('app_trajet_detail', ['id' => $trip->getId()]);
        }

        $reservation = new Reservation();
        $reservation->setPassenger($user);
        $reservation->setTrip($trip);
        $reservation->setStatus('pending');
        $reservation->setSeatsBooked(1);

        $trip->setSeatsAvailable($trip->getSeatsAvailable() - 1);

        $entityManager->persist($reservation);
        $entityManager->flush();

        $mailerService->sendNewReservationToDriver($reservation);

        $this->addFlash('success', 'Réservation envoyée au conducteur!');

        return $this->redirectToRoute('app_reservation_my');
    }

    #[Route('/reservation/{id}/annuler', name: 'app_reservation_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_PASSAGER')]
    public function cancel(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager,
        MailerService $mailerService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getId() !== $reservation->getPassenger()?->getId()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('cancel'.$reservation->getId(), $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $status = $reservation->getStatus();
        if (!\in_array($status, ['pending', 'confirmed'], true)) {
            $this->addFlash('warning', 'Cette réservation est déjà annulée.');

            return $this->redirectToRoute('app_reservation_my');
        }

        $trip = $reservation->getTrip();
        if (null !== $trip && $trip->getDepartureDateTime() < new \DateTimeImmutable()) {
            $this->addFlash('warning', 'Vous ne pouvez plus annuler : le trajet a déjà eu lieu.');

            return $this->redirectToRoute('app_reservation_my');
        }

        $reservation->setStatus('cancelled');
        if (null !== $trip) {
            $trip->setSeatsAvailable($trip->getSeatsAvailable() + $reservation->getSeatsBooked());
        }
        $mailerService->sendCancellationNotice($reservation, 'passenger');

        $entityManager->flush();

        $this->addFlash('success', 'Réservation annulée.');

        return $this->redirectToRoute('app_reservation_my');
    }

    #[Route('/reservation/{id}/confirmer', name: 'app_reservation_confirm', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_CONDUCTEUR')]
    public function confirm(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager,
        MailerService $mailerService,
    ): Response {
        $trip = $reservation->getTrip();
        if (null === $trip) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(TripVoter::EDIT, $trip);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('confirm'.$reservation->getId(), $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ('pending' !== $reservation->getStatus()) {
            $this->addFlash('warning', 'Cette demande ne peut pas être confirmée.');

            return $this->redirectToRoute('app_trip_passengers', ['tripId' => $trip->getId()]);
        }

        $reservation->setStatus('confirmed');
        $entityManager->flush();

        $mailerService->sendConfirmationToPassenger($reservation);

        $this->addFlash('success', 'Réservation confirmée.');

        return $this->redirectToRoute('app_trip_passengers', ['tripId' => $trip->getId()]);
    }

    #[Route('/reservation/{id}/refuser', name: 'app_reservation_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_CONDUCTEUR')]
    public function reject(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager,
        MailerService $mailerService,
    ): Response {
        $trip = $reservation->getTrip();
        if (null === $trip) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(TripVoter::EDIT, $trip);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('reject'.$reservation->getId(), $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $status = $reservation->getStatus();
        if (!\in_array($status, ['pending', 'confirmed'], true)) {
            $this->addFlash('warning', 'Cette réservation ne peut plus être modifiée.');

            return $this->redirectToRoute('app_trip_passengers', ['tripId' => $trip->getId()]);
        }

        $reservation->setStatus('cancelled');
        $trip->setSeatsAvailable($trip->getSeatsAvailable() + $reservation->getSeatsBooked());
        $mailerService->sendCancellationNotice($reservation, 'driver');

        $entityManager->flush();

        $this->addFlash('success', 'La réservation a été refusée.');

        return $this->redirectToRoute('app_trip_passengers', ['tripId' => $trip->getId()]);
    }

    #[Route('/mes-reservations', name: 'app_reservation_my', methods: ['GET'])]
    #[IsGranted('ROLE_PASSAGER')]
    public function myReservations(ReservationRepository $reservationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $reservations = $reservationRepository->findForPassengerOrdered($user);

        $now = new \DateTimeImmutable();
        $active = [];
        $completed = [];
        $cancelledList = [];

        foreach ($reservations as $reservation) {
            if ('cancelled' === $reservation->getStatus()) {
                $cancelledList[] = $reservation;

                continue;
            }

            $departure = $reservation->getTrip()?->getDepartureDateTime();
            if (null !== $departure && $departure < $now) {
                $completed[] = $reservation;
            } else {
                $active[] = $reservation;
            }
        }

        return $this->render('reservation/my_reservations.html.twig', [
            'activeReservations' => $active,
            'completedReservations' => $completed,
            'cancelledReservations' => $cancelledList,
            'now' => $now,
        ]);
    }

    #[Route('/mes-trajets/{tripId}/passagers', name: 'app_trip_passengers', requirements: ['tripId' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_CONDUCTEUR')]
    public function tripPassengers(
        #[MapEntity(id: 'tripId')]
        Trip $trip,
        ReservationRepository $reservationRepository,
    ): Response {
        $this->denyAccessUnlessGranted(TripVoter::EDIT, $trip);

        $reservations = $reservationRepository->findByTripOrdered($trip);

        $summary = ['confirmed' => 0, 'pending' => 0, 'cancelled' => 0];
        foreach ($reservations as $r) {
            $st = $r->getStatus();
            if (isset($summary[$st])) {
                ++$summary[$st];
            }
        }

        return $this->render('reservation/passengers.html.twig', [
            'trip' => $trip,
            'reservations' => $reservations,
            'summary' => $summary,
        ]);
    }

}
