<?php

namespace App\DataFixtures;

use App\Entity\Rating;
use App\Entity\Reservation;
use App\Entity\Trip;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $students = [
            ['sara@etudiant.tn', 'Sara', 'Ben Ammar', '+216 55 111 222', 'Université de Tunis'],
            ['mehdi@etudiant.tn', 'Mehdi', 'Trabelsi', '+216 55 333 444', 'Université de Sousse'],
            ['ines@etudiant.tn', 'Inès', 'Gharbi', '+216 55 555 666', 'Université de Sfax'],
            ['youssef@etudiant.tn', 'Youssef', 'Mansour', '+216 55 777 888', 'Université de Monastir'],
            ['nour@etudiant.tn', 'Nour', 'Jlassi', '+216 55 999 000', 'Université de Bizerte'],
        ];

        $userEntities = [];
        foreach ($students as $row) {
            $u = new User();
            $u->setEmail($row[0]);
            $u->setFirstName($row[1]);
            $u->setLastName($row[2]);
            $u->setPhone($row[3]);
            $u->setUniversity($row[4]);
            $u->setBio('Étudiant·e CovoitUni — trajets réguliers entre campus.');
            $u->setRoles([]);
            $u->setPassword($this->passwordHasher->hashPassword($u, 'student123'));
            $u->setIsVerified(true);
            $manager->persist($u);
            $userEntities[] = $u;
        }

        $admin = new User();
        $admin->setEmail('admin@covoituni.tn');
        $admin->setFirstName('Admin');
        $admin->setLastName('CovoitUni');
        $admin->setUniversity('CovoitUni');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setIsVerified(true);
        $manager->persist($admin);

        $manager->flush();

        $cities = ['Tunis', 'Sousse', 'Sfax', 'Monastir', 'Bizerte'];
        $trips = [];
        for ($i = 0; $i < 15; ++$i) {
            $from = $cities[$i % 5];
            $to = $cities[($i + 2) % 5];
            if ($from === $to) {
                $to = $cities[($i + 3) % 5];
            }

            $driver = $userEntities[$i % \count($userEntities)];
            $daysOffset = -10 + ($i * 2);
            $hour = 7 + ($i % 12);

            $trip = new Trip();
            $trip->setDriver($driver);
            $trip->setDeparture($from);
            $trip->setDestination($to);
            $trip->setDepartureDateTime((new \DateTimeImmutable('today'))->modify(sprintf('%+d days', $daysOffset))->setTime($hour, 30));
            $trip->setSeatsTotal(4);
            $trip->setSeatsAvailable(4);
            $trip->setPricePerSeat($i % 4 === 0 ? null : (string) (3 + ($i % 5)));
            $trip->setDescription(sprintf('Trajet %s → %s — places confortables, musique cool.', $from, $to));
            $trip->setIsActive(true);
            $manager->persist($trip);
            $trips[] = $trip;
        }

        $manager->flush();

        $statusCycle = ['pending', 'pending', 'confirmed', 'confirmed', 'cancelled'];
        $reservations = [];
        for ($r = 0; $r < 20; ++$r) {
            $trip = $trips[$r % \count($trips)];
            $driverId = $trip->getDriver()->getId();
            $passenger = $userEntities[($r + 1) % \count($userEntities)];
            if ($passenger->getId() === $driverId) {
                $passenger = $userEntities[($r + 2) % \count($userEntities)];
            }

            $res = new Reservation();
            $res->setTrip($trip);
            $res->setPassenger($passenger);
            $res->setStatus($statusCycle[$r % \count($statusCycle)]);
            $res->setSeatsBooked(1);
            $manager->persist($res);
            $reservations[] = $res;
        }

        $manager->flush();

        foreach ($trips as $trip) {
            $used = 0;
            foreach ($trip->getReservations() as $res) {
                if (\in_array($res->getStatus(), ['pending', 'confirmed'], true)) {
                    $used += $res->getSeatsBooked();
                }
            }
            $trip->setSeatsAvailable(max(0, $trip->getSeatsTotal() - $used));
        }

        $manager->flush();

        $ratingCount = 0;
        foreach ($reservations as $res) {
            if ($ratingCount >= 10) {
                break;
            }
            if ('confirmed' !== $res->getStatus()) {
                continue;
            }
            $trip = $res->getTrip();
            if (null === $trip || $trip->getDepartureDateTime() > new \DateTimeImmutable()) {
                continue;
            }

            $driver = $trip->getDriver();
            $passenger = $res->getPassenger();
            $rating = new Rating();
            $rating->setReviewer($passenger);
            $rating->setDriver($driver);
            $rating->setScore(3 + ($ratingCount % 3));
            $rating->setComment(sprintf('Super trajet %s → %s, merci !', $trip->getDeparture(), $trip->getDestination()));
            $rating->setReservation($res);
            $res->setIsRated(true);
            $manager->persist($rating);
            ++$ratingCount;
        }

        $manager->flush();
    }
}
