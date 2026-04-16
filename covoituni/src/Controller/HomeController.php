<?php

namespace App\Controller;

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
        return $this->render('home/index.html.twig', [
            'recentTrips' => $tripRepository->findRecentActive(6),
            'totalUsers' => $userRepository->countUsers(),
            'totalTrips' => $tripRepository->countActiveTrips(),
        ]);
    }
}
