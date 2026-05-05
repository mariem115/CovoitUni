<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Entity\User;
use App\Form\TripType;
use App\Repository\RatingRepository;
use App\Repository\ReservationRepository;
use App\Repository\TripRepository;
use App\Security\TripVoter;
use App\Service\LocationData;
use App\Service\TripService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TripController extends AbstractController
{
    #[Route('/trajet/{id}', name: 'app_trajet_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function detail(
        int $id,
        TripRepository $tripRepository,
        RatingRepository $ratingRepository,
        TripService $tripService,
    ): Response {
        $trip = $tripRepository->findPublicDetailById($id);
        if (!$trip instanceof Trip) {
            throw $this->createNotFoundException();
        }

        if (!$trip->isActive()) {
            throw $this->createNotFoundException();
        }

        $driver = $trip->getDriver();
        if (!$driver instanceof User) {
            throw $this->createNotFoundException();
        }

        $ratings = $ratingRepository->findReceivedForDriverOrdered($driver);
        $recentRatings = \array_slice($ratings, 0, 3);
        $bookInfo = $tripService->canUserBook($trip, $this->getUser() instanceof User ? $this->getUser() : null);

        return $this->render('trajet/detail.html.twig', [
            'trip' => $trip,
            'driver' => $driver,
            'recentRatings' => $recentRatings,
            'ratingsCount' => \count($ratings),
            'bookInfo' => $bookInfo,
        ]);
    }

    #[Route('/trajets', name: 'app_trip_index', methods: ['GET'])]
    #[IsGranted('ROLE_PASSAGER')]
    public function index(
        Request $request,
        TripRepository $tripRepository,
        PaginatorInterface $paginator,
    ): Response {
        $departure = $request->query->getString('departure');
        $destination = $request->query->getString('destination');
        $dateStr = $request->query->getString('date');
        $minSeats = max(1, (int) $request->query->get('seats', 1));
        $maxPriceStr = trim($request->query->getString('maxPrice'));
        $maxPrice = '' !== $maxPriceStr && is_numeric($maxPriceStr) ? $maxPriceStr : null;
        $onlyWithAvailability = $request->query->getInt('disponible', 1) === 1;

        $date = null;
        if ($dateStr !== '') {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateStr) ?: null;
        }

        $qb = $tripRepository->createSearchTripsQueryBuilder(
            $departure,
            $destination,
            $date,
            $minSeats,
            $maxPrice,
            $onlyWithAvailability,
        );

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            9,
        );

        if ($this->wantsTripIndexJson($request)) {
            return new JsonResponse([
                'html' => $this->renderView('trip/_trip_search_fragment.html.twig', [
                    'pagination' => $pagination,
                ]),
                'meta' => sprintf(
                    '%d résultat%s',
                    $pagination->getTotalItemCount(),
                    $pagination->getTotalItemCount() > 1 ? 's' : '',
                ),
            ]);
        }

        return $this->render('trip/index.html.twig', [
            'pagination' => $pagination,
            'paginationTotal' => $pagination->getTotalItemCount(),
            'departure' => $departure,
            'destination' => $destination,
            'date' => $dateStr,
            'seats' => $minSeats,
            'maxPrice' => $maxPriceStr,
            'disponible' => $onlyWithAvailability ? 1 : 0,
            'villes' => LocationData::VILLES,
        ]);
    }

    #[Route('/trajets/nouveau', name: 'app_trip_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CONDUCTEUR')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $trip = new Trip();
        $trip->setDriver($user);

        $form = $this->createForm(TripType::class, $trip, [
            'validation_groups' => ['Default', 'trip_create'],
        ]);
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
    #[IsGranted('ROLE_USER')]
    public function show(
        Trip $trip,
        ReservationRepository $reservationRepository,
        TripService $tripService,
    ): Response {
        $user = $this->getUser();
        if (!$trip->isActive() && (!$user instanceof User || $user->getId() !== $trip->getDriver()?->getId())) {
            throw $this->createNotFoundException();
        }

        $userReservation = null;
        if ($user instanceof User) {
            $userReservation = $reservationRepository->findBlockingReservationForPassenger($trip, $user);
        }

        $bookInfo = $tripService->canUserBook($trip, $user instanceof User ? $user : null);
        $similarTrips = $tripService->getSimilarTrips($trip, 3);
        $tripTimingLabel = $tripService->formatTripDuration($trip);

        return $this->render('trip/show.html.twig', [
            'trip' => $trip,
            'userReservation' => $userReservation,
            'bookInfo' => $bookInfo,
            'similarTrips' => $similarTrips,
            'tripTimingLabel' => $tripTimingLabel,
        ]);
    }

    #[Route('/trajets/{id}/modifier', name: 'app_trip_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CONDUCTEUR')]
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
    #[IsGranted('ROLE_CONDUCTEUR')]
    public function delete(Request $request, Trip $trip, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(TripVoter::DELETE, $trip);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('delete'.$trip->getId(), $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $trip->setIsActive(false);
        $entityManager->flush();

        $this->addFlash('success', 'Le trajet a été retiré de la liste publique.');

        return $this->redirectToRoute('app_profile_trips');
    }

    private function normalizeTripPrice(Trip $trip): void
    {
        $p = $trip->getPricePerSeat();
        if (null === $p || '' === trim((string) $p)) {
            $trip->setPricePerSeat(null);
        }
    }

    private function wantsTripIndexJson(Request $request): bool
    {
        if ('json' === $request->query->getString('format')) {
            return true;
        }

        if ($request->isXmlHttpRequest()) {
            return true;
        }

        $accept = $request->headers->get('Accept', '');

        return str_contains($accept, 'application/json');
    }
}
