<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TripRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(TripRepository $tripRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin');
            }
            if ($this->isGranted('ROLE_CONDUCTEUR')) {
                return $this->redirectToRoute('app_profile_trips');
            }
            if ($this->isGranted('ROLE_PASSAGER')) {
                return $this->redirectToRoute('app_trip_index');
            }

            return $this->redirectToRoute('app_profile_my');
        }

        return $this->render('home/index.html.twig', [
            'totalUsers' => $userRepository->countUsers(),
            'totalTrips' => $tripRepository->countActiveTrips(),
        ]);
    }
}
