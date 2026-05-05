<?php
namespace App\Controller;
use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\RatingRepository;
use App\Repository\TripRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            return $this->redirectToRoute('app_profile_my');
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/mon-profil', name: 'app_profile_my')]
    #[IsGranted('ROLE_USER')]
    public function myProfile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->redirectToRoute('app_profile_show', ['id' => $user->getId()]);
    }

    #[Route('/profil/{id}', name: 'app_profile_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        int $id,
        UserRepository $userRepository,
        TripRepository $tripRepository,
        RatingRepository $ratingRepository,
        UserService $userService,
    ): Response {
        $user = $userRepository->find($id);
        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        $activeTrips = $tripRepository->findActiveByDriver($user);
        $receivedRatings = $ratingRepository->findReceivedForDriverOrdered($user);
        $avgRating = $userService->getAverageRating($user);
        $tripStats = $userService->getTripStats($user);

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'activeTrips' => $activeTrips,
            'receivedRatings' => $receivedRatings,
            'avgRating' => $avgRating,
            'starDisplay' => $userService->getStarDisplay($avgRating),
            'tripStats' => $tripStats,
        ]);
    }

    #[Route('/conducteur/{id}/profil-public', name: 'app_conducteur_public_profile', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function conducteurPublicProfile(
        int $id,
        UserRepository $userRepository,
        TripRepository $tripRepository,
        RatingRepository $ratingRepository,
        UserService $userService,
    ): Response {
        return $this->show($id, $userRepository, $tripRepository, $ratingRepository, $userService);
    }

    #[Route('/profil/modifier', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user, [
            'include_vehicle_fields' => $this->isGranted('ROLE_CONDUCTEUR'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form->get('profilePhoto')->getData();
            if (null !== $photo) {
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/images/uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $old = $user->getProfilePhoto();
                if (null !== $old && is_file($uploadDir.'/'.$old)) {
                    @unlink($uploadDir.'/'.$old);
                }

                $original = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $original) ?: 'photo';
                $ext = $photo->guessExtension() ?: 'jpg';
                $newFilename = $safe.'-'.bin2hex(random_bytes(8)).'.'.$ext;
                $photo->move($uploadDir, $newFilename);
                $user->setProfilePhoto($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour.');

            return $this->redirectToRoute('app_profile_my');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/mes-trajets', name: 'app_profile_trips')]
    #[IsGranted('ROLE_CONDUCTEUR')]
    public function myTrips(TripRepository $tripRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $trips = $tripRepository->findByDriverWithReservations($user);
        $now = new \DateTimeImmutable();
        $upcoming = [];
        $past = [];
        foreach ($trips as $trip) {
            if ($trip->getDepartureDateTime() >= $now) {
                $upcoming[] = $trip;
            } else {
                $past[] = $trip;
            }
        }

        usort($upcoming, static fn ($a, $b) => $a->getDepartureDateTime() <=> $b->getDepartureDateTime());

        return $this->render('profile/my_trips.html.twig', [
            'upcomingTrips' => $upcoming,
            'pastTrips' => $past,
        ]);
    }
}
