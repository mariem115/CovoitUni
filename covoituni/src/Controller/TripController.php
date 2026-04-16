<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Trip;
use App\Entity\User;
use App\Form\TripType;
use App\Repository\ReservationRepository;
use App\Repository\TripRepository;
use App\Security\TripVoter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TripController extends AbstractController
{
    #[Route('/trajets', name: 'app_trip_index', methods: ['GET'])]
    public function index(Request $request, TripRepository $tripRepository): Response
    {
        $departure = $request->query->getString('departure');
        $destination = $request->query->getString('destination');
        $dateStr = $request->query->getString('date');
        $minSeats = max(1, (int) $request->query->get('seats', 1));
        $page = max(1, (int) $request->query->get('page', 1));

        $date = null;
        if ($dateStr !== '') {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateStr) ?: null;
        }

        $hasSearch = trim($departure) !== '' || trim($destination) !== '' || null !== $date;

        if ($hasSearch) {
            $qb = $tripRepository->createSearchTripsQueryBuilder($departure, $destination, $date, $minSeats);
        } else {
            $qb = $tripRepository->createActiveTripsQueryBuilder()
                ->andWhere('t.seatsAvailable >= :minSeats')
                ->setParameter('minSeats', $minSeats);
        }

        $qb->setFirstResult(($page - 1) * 9)->setMaxResults(9);
        $paginator = new Paginator($qb->getQuery(), false);
        $totalItems = $paginator->count();
        $totalPages = max(1, (int) ceil($totalItems / 9));

        return $this->render('trip/index.html.twig', [
            'trips' => iterator_to_array($paginator),
            'departure' => $departure,
            'destination' => $destination,
            'date' => $dateStr,
            'seats' => $minSeats,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
        ]);
    }

    #[Route('/trajets/nouveau', name: 'app_trip_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $trip = new Trip();
        $trip->setDriver($user);

        $form = $this->createForm(TripType::class, $trip);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->normalizeTripPrice($trip);
            $trip->setSeatsAvailable($trip->getSeatsTotal());
            $entityManager->persist($trip);
            $entityManager->flush();

            $this->addFlash('success', 'Votre trajet a été publié.');

            return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
        }

        return $this->render('trip/new.html.twig', [
            'form' => $form,
            'trip' => $trip,
        ]);
    }

    #[Route('/trajets/{id}', name: 'app_trip_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Trip $trip,
        ReservationRepository $reservationRepository,
        TripRepository $tripRepository,
    ): Response {
        $user = $this->getUser();
        if (!$trip->isActive() && (!$user instanceof User || $user->getId() !== $trip->getDriver()?->getId())) {
            throw $this->createNotFoundException();
        }

        $userReservation = null;
        if ($user instanceof User) {
            $userReservation = $reservationRepository->findBlockingReservationForPassenger($trip, $user);
        }

        $canBook = $user instanceof User
            && $user->getId() !== $trip->getDriver()?->getId()
            && $trip->getSeatsAvailable() > 0
            && $trip->isActive()
            && null === $userReservation;

        $similarTrips = $tripRepository->findSimilarActiveTrips($trip, 4);
        $passengers = [];
        if ($user instanceof User && $user->getId() === $trip->getDriver()?->getId()) {
            $passengers = $reservationRepository->findByTripOrdered($trip);
        }

        return $this->render('trip/show.html.twig', [
            'trip' => $trip,
            'userReservation' => $userReservation,
            'canBook' => $canBook,
            'similarTrips' => $similarTrips,
            'passengers' => $passengers,
        ]);
    }

    #[Route('/trajets/{id}/modifier', name: 'app_trip_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Trip $trip, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(TripVoter::EDIT, $trip);

        $oldTotal = $trip->getSeatsTotal();
        $oldAvailable = $trip->getSeatsAvailable();

        $form = $this->createForm(TripType::class, $trip);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->normalizeTripPrice($trip);
            $newTotal = $trip->getSeatsTotal();
            if ($newTotal !== $oldTotal) {
                $booked = $oldTotal - $oldAvailable;
                $newAvailable = $newTotal - $booked;
                $newAvailable = max(0, min($newAvailable, $newTotal));
                $trip->setSeatsAvailable($newAvailable);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Trajet mis à jour.');

            return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
        }

        return $this->render('trip/edit.html.twig', [
            'form' => $form,
            'trip' => $trip,
        ]);
    }

    #[Route('/trajets/{id}/supprimer', name: 'app_trip_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Trip $trip, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(TripVoter::DELETE, $trip);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('delete_trip_'.$trip->getId(), $token)) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
        }

        $trip->setIsActive(false);
        $entityManager->flush();

        $this->addFlash('success', 'Le trajet a été retiré de la liste publique.');

        return $this->redirectToRoute('app_profile_trips');
    }

    #[Route('/trajets/{id}/reserver', name: 'app_trip_reserve', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reserve(Request $request, Trip $trip, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository): Response
    {
        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('reserve_trip_'.$trip->getId(), $token)) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->getId() === $trip->getDriver()?->getId()
            || !$trip->isActive()
            || $trip->getSeatsAvailable() < 1) {
            $this->addFlash('danger', 'Réservation impossible.');

            return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
        }

        if (null !== $reservationRepository->findBlockingReservationForPassenger($trip, $user)) {
            $this->addFlash('info', 'Vous avez déjà une réservation pour ce trajet.');

            return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
        }

        $requested = max(1, (int) $request->request->get('seats', 1));
        $seats = min($requested, $trip->getSeatsAvailable());

        $reservation = new Reservation();
        $reservation->setTrip($trip);
        $reservation->setPassenger($user);
        $reservation->setStatus('pending');
        $reservation->setSeatsBooked($seats);

        $trip->setSeatsAvailable($trip->getSeatsAvailable() - $seats);

        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Demande de réservation envoyée au conducteur.');

        return $this->redirectToRoute('app_trip_show', ['id' => $trip->getId()]);
    }

    private function normalizeTripPrice(Trip $trip): void
    {
        $p = $trip->getPricePerSeat();
        if (null === $p || '' === trim((string) $p)) {
            $trip->setPricePerSeat(null);
        }
    }
}
