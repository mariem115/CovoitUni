<?php

namespace App\Entity;

use App\Repository\TripRepository;
use App\Service\LocationData;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TripRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Trip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\Choice(callback: [LocationData::class, 'getVillesChoices'])]
    private ?string $departure = null;

    #[ORM\Column(length: 200)]
    #[Assert\Choice(callback: [LocationData::class, 'getVillesChoices'])]
    private ?string $destination = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $departureDateTime = null;

    #[ORM\Column]
    private ?int $seatsTotal = null;

    #[ORM\Column]
    private ?int $seatsAvailable = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $pricePerSeat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'tripsAsDriver', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $driver = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'trip', orphanRemoval: true)]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeparture(): ?string
    {
        return $this->departure;
    }

    public function setDeparture(string $departure): static
    {
        $this->departure = $departure;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getDepartureDateTime(): ?\DateTimeImmutable
    {
        return $this->departureDateTime;
    }

    public function setDepartureDateTime(\DateTimeImmutable $departureDateTime): static
    {
        $this->departureDateTime = $departureDateTime;

        return $this;
    }

    public function getSeatsTotal(): ?int
    {
        return $this->seatsTotal;
    }

    public function setSeatsTotal(int $seatsTotal): static
    {
        $this->seatsTotal = $seatsTotal;

        return $this;
    }

    public function getSeatsAvailable(): ?int
    {
        return $this->seatsAvailable;
    }

    public function setSeatsAvailable(int $seatsAvailable): static
    {
        $this->seatsAvailable = $seatsAvailable;

        return $this;
    }

    public function getPricePerSeat(): ?string
    {
        return $this->pricePerSeat;
    }

    public function setPricePerSeat(?string $pricePerSeat): static
    {
        $this->pricePerSeat = $pricePerSeat;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDriver(): ?User
    {
        return $this->driver;
    }

    public function setDriver(?User $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setTrip($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        $this->reservations->removeElement($reservation);

        return $this;
    }

    /**
     * Places réservées (demandes en attente ou confirmées).
     */
    public function getBookedPassengerSeats(): int
    {
        $n = 0;
        foreach ($this->reservations as $r) {
            if (\in_array($r->getStatus(), ['pending', 'confirmed'], true)) {
                $n += $r->getSeatsBooked();
            }
        }

        return $n;
    }

    public function isReservable(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if (($this->seatsAvailable ?? 0) < 1) {
            return false;
        }

        return null !== $this->departureDateTime && $this->departureDateTime > new \DateTimeImmutable();
    }

    public function getEaDeparture(): string
    {
        return $this->departureDateTime instanceof \DateTimeInterface ? $this->departureDateTime->format('d/m/Y H:i') : '—';
    }

    public function getEaCreatedAt(): string
    {
        return $this->createdAt instanceof \DateTimeInterface ? $this->createdAt->format('d/m/Y H:i') : '—';
    }

    public function __toString(): string
    {
        $when = $this->departureDateTime?->format('d/m/Y H:i') ?? '—';

        return sprintf('%s → %s (%s)', $this->departure ?? '?', $this->destination ?? '?', $when);
    }
}
