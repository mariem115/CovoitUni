<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TripRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/profil/mes-trajets', name: 'app_profile_trips')]
    #[IsGranted('ROLE_USER')]
    public function myTrips(TripRepository $tripRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('profile/my_trips.html.twig', [
            'trips' => $tripRepository->findByDriver($user),
        ]);
    }
}
