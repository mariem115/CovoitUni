<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $university = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $vehiculeMarque = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $vehiculeModele = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $vehiculeCouleur = null;

    #[ORM\Column(nullable: true)]
    private ?int $vehiculeAnnee = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePhoto = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isVerified = false;

    /**
     * @var Collection<int, Trip>
     */
    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'driver', orphanRemoval: true)]
    private Collection $tripsAsDriver;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'passenger', orphanRemoval: true)]
    private Collection $reservations;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'reviewer', orphanRemoval: true)]
    private Collection $givenRatings;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'driver', orphanRemoval: true)]
    private Collection $receivedRatings;

    public function __construct()
    {
        $this->initializeAssociationCollections();
    }

    private function initializeAssociationCollections(): void
    {
        $this->tripsAsDriver = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->givenRatings = new ArrayCollection();
        $this->receivedRatings = new ArrayCollection();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getUniversity(): ?string
    {
        return $this->university;
    }

    public function setUniversity(?string $university): static
    {
        $this->university = $university;

        return $this;
    }

    public function getVehiculeMarque(): ?string
    {
        return $this->vehiculeMarque;
    }

    public function setVehiculeMarque(?string $vehiculeMarque): static
    {
        $this->vehiculeMarque = $vehiculeMarque;

        return $this;
    }

    public function getVehiculeModele(): ?string
    {
        return $this->vehiculeModele;
    }

    public function setVehiculeModele(?string $vehiculeModele): static
    {
        $this->vehiculeModele = $vehiculeModele;

        return $this;
    }

    public function getVehiculeCouleur(): ?string
    {
        return $this->vehiculeCouleur;
    }

    public function setVehiculeCouleur(?string $vehiculeCouleur): static
    {
        $this->vehiculeCouleur = $vehiculeCouleur;

        return $this;
    }

    public function getVehiculeAnnee(): ?int
    {
        return $this->vehiculeAnnee;
    }

    public function setVehiculeAnnee(?int $vehiculeAnnee): static
    {
        $this->vehiculeAnnee = $vehiculeAnnee;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getProfilePhoto(): ?string
    {
        return $this->profilePhoto;
    }

    public function setProfilePhoto(?string $profilePhoto): static
    {
        $this->profilePhoto = $profilePhoto;

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

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getFullName(): string
    {
        return trim(($this->firstName ?? '').' '.($this->lastName ?? ''));
    }

    public function getAverageRating(): float
    {
        if ($this->receivedRatings->isEmpty()) {
            return 0.0;
        }

        $sum = 0;
        foreach ($this->receivedRatings as $rating) {
            $sum += $rating->getScore();
        }

        return $sum / $this->receivedRatings->count();
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getEaCreatedAt(): string
    {
        return $this->createdAt instanceof \DateTimeInterface ? $this->createdAt->format('d/m/Y H:i') : '—';
    }

    /**
     * État minimal pour la session / jeton Security : ne pas inclure les collections Doctrine,
     * sinon PHP sérialise tout le graphe (trajets → réservations → …) et peut bloquer >120 s au chargement.
     *
     * @see PasswordAuthenticatedUserInterface (Symfony 7.3 — empreinte crc32c sur le hash)
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'roles' => $this->roles,
            'password' => null !== $this->password ? hash('crc32c', $this->password) : null,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->initializeAssociationCollections();

        if (\array_key_exists('email', $data)) {
            $this->id = $data['id'] ?? null;
            $this->email = $data['email'];
            $this->roles = \is_array($data['roles'] ?? null) ? $data['roles'] : [];
            $this->password = $data['password'] ?? null;
            $this->firstName = $data['firstName'] ?? null;
            $this->lastName = $data['lastName'] ?? null;

            return;
        }

        // Ancienne session : propriétés privées sous forme "\0Class\0prop"
        $prefix = "\0".self::class."\0";
        $this->id = $data[$prefix.'id'] ?? null;
        $this->email = $data[$prefix.'email'] ?? '';
        $this->roles = \is_array($data[$prefix.'roles'] ?? null) ? $data[$prefix.'roles'] : [];
        $pw = $data[$prefix.'password'] ?? null;
        if (null !== $pw && '' !== $pw && 8 !== \strlen((string) $pw)) {
            $this->password = hash('crc32c', (string) $pw);
        } else {
            $this->password = $pw;
        }
        $this->firstName = $data[$prefix.'firstName'] ?? null;
        $this->lastName = $data[$prefix.'lastName'] ?? null;
    }

    /**
     * @return Collection<int, Trip>
     */
    public function getTripsAsDriver(): Collection
    {
        return $this->tripsAsDriver;
    }

    public function addTripsAsDriver(Trip $tripsAsDriver): static
    {
        if (!$this->tripsAsDriver->contains($tripsAsDriver)) {
            $this->tripsAsDriver->add($tripsAsDriver);
            $tripsAsDriver->setDriver($this);
        }

        return $this;
    }

    public function removeTripsAsDriver(Trip $tripsAsDriver): static
    {
        $this->tripsAsDriver->removeElement($tripsAsDriver);

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
            $reservation->setPassenger($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        $this->reservations->removeElement($reservation);

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getGivenRatings(): Collection
    {
        return $this->givenRatings;
    }

    public function addGivenRating(Rating $givenRating): static
    {
        if (!$this->givenRatings->contains($givenRating)) {
            $this->givenRatings->add($givenRating);
            $givenRating->setReviewer($this);
        }

        return $this;
    }

    public function removeGivenRating(Rating $givenRating): static
    {
        $this->givenRatings->removeElement($givenRating);

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getReceivedRatings(): Collection
    {
        return $this->receivedRatings;
    }

    public function addReceivedRating(Rating $receivedRating): static
    {
        if (!$this->receivedRatings->contains($receivedRating)) {
            $this->receivedRatings->add($receivedRating);
            $receivedRating->setDriver($this);
        }

        return $this;
    }

    public function removeReceivedRating(Rating $receivedRating): static
    {
        $this->receivedRatings->removeElement($receivedRating);

        return $this;
    }
}
