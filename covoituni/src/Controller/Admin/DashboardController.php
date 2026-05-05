<?php

namespace App\Controller\Admin;

use App\Entity\Rating;
use App\Entity\Reservation;
use App\Entity\Trip;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function index(): Response
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        $tripRepo = $this->entityManager->getRepository(Trip::class);
        $reservationRepo = $this->entityManager->getRepository(Reservation::class);

        $stats = [
            'total_users' => (int) $userRepo->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            'active_trips' => (int) $tripRepo->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.isActive = :active')
                ->setParameter('active', true)
                ->getQuery()
                ->getSingleScalarResult(),
            'pending_reservations' => (int) $reservationRepo->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.status = :pending')
                ->setParameter('pending', 'pending')
                ->getQuery()
                ->getSingleScalarResult(),
            'avg_rating' => $this->entityManager->createQueryBuilder()
                ->select('AVG(rt.score)')
                ->from(Rating::class, 'rt')
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        $avg = $stats['avg_rating'];
        $stats['avg_rating'] = null === $avg ? null : round((float) $avg, 2);

        $recentReservations = $reservationRepo->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recent_reservations' => $recentReservations,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('CovoitUni Admin')
            ->disableDarkMode();
    }

    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa-solid fa-chart-line');
        yield MenuItem::linkTo(UserCrudController::class, 'Users', 'fa-solid fa-users');
        yield MenuItem::linkTo(TripCrudController::class, 'Trips', 'fa-solid fa-car');
        yield MenuItem::linkTo(ReservationCrudController::class, 'Reservations', 'fa-solid fa-calendar-check');
        yield MenuItem::linkTo(RatingCrudController::class, 'Ratings', 'fa-solid fa-star');
    }
}
