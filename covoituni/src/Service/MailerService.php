<?php

namespace App\Service;

use App\Entity\Reservation;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailerService
{
    private const DEFAULT_FROM = 'noreply@covoituni.example';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $router,
    ) {
    }

    public function sendNewReservationToDriver(Reservation $res): void
    {
        $trip = $res->getTrip();
        $driver = $trip?->getDriver();
        if (null === $driver || null === $driver->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address(self::DEFAULT_FROM, 'CovoitUni'))
            ->to($driver->getEmail())
            ->subject('Nouvelle demande de réservation — CovoitUni')
            ->htmlTemplate('emails/reservation_new_driver.html.twig')
            ->context([
                'reservation' => $res,
                'trip' => $trip,
                'passenger' => $res->getPassenger(),
                'manageUrl' => $this->router->generate('app_trip_passengers', ['tripId' => $trip->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        $this->mailer->send($email);
    }

    public function sendConfirmationToPassenger(Reservation $res): void
    {
        $passenger = $res->getPassenger();
        $trip = $res->getTrip();
        if (null === $passenger || null === $passenger->getEmail() || null === $trip) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address(self::DEFAULT_FROM, 'CovoitUni'))
            ->to($passenger->getEmail())
            ->subject('Votre réservation est confirmée — CovoitUni')
            ->htmlTemplate('emails/reservation_confirmed_passenger.html.twig')
            ->context([
                'reservation' => $res,
                'trip' => $trip,
                'passenger' => $passenger,
                'driver' => $trip->getDriver(),
                'myReservationsUrl' => $this->router->generate('app_reservation_my', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        $this->mailer->send($email);
    }

    public function sendCancellationNotice(Reservation $res, string $cancelledBy): void
    {
        $trip = $res->getTrip();
        if (null === $trip) {
            return;
        }

        if ('passenger' === $cancelledBy) {
            $recipient = $trip->getDriver();
        } elseif ('driver' === $cancelledBy) {
            $recipient = $res->getPassenger();
        } else {
            return;
        }

        if (null === $recipient || null === $recipient->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address(self::DEFAULT_FROM, 'CovoitUni'))
            ->to($recipient->getEmail())
            ->subject('Annulation de réservation — CovoitUni')
            ->htmlTemplate('emails/reservation_cancelled_notice.html.twig')
            ->context([
                'reservation' => $res,
                'trip' => $trip,
                'cancelledBy' => $cancelledBy,
                'otherParty' => 'passenger' === $cancelledBy ? $res->getPassenger() : $trip->getDriver(),
            ]);

        $this->mailer->send($email);
    }
}
